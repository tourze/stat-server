<?php

namespace stat;

use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Base\Helper\Arr;

class StatServer
{

    /**
     * @var string 放统计数据的目录
     */
    public static $statDir = 'stat/';

    /**
     * @var string 存放统计日志的目录
     */
    public static $logDir = 'log/';

    /**
     * 批量请求
     *
     * @param array $requestBufferArray ['ip:port'=>req_buf, 'ip:port'=>req_buf, ...]
     * @return mixed multitype:unknown string
     */
    public static function multiRequest($requestBufferArray)
    {
        Base::getLog()->info(__METHOD__ . ' call multiRequest - start', [
            'arg' => $requestBufferArray,
        ]);

        Cache::$lastSuccessIpArray = [];
        $clientArray = $sockToIP = $ipList = $sockToAddress = [];
        foreach ($requestBufferArray as $address => $buffer)
        {
            $temp = explode(':', $address);
            $ip = array_shift($temp);

            Base::getLog()->info(__METHOD__ . ' loop to get remote buffer', [
                'ip'      => $ip,
                'address' => $address,
                'buffer'  => $buffer,
            ]);

            $ipList[$ip] = $ip;
            $client = stream_socket_client("tcp://$address", $errNumber, $errorMsg, 1);
            if ( ! $client)
            {
                Base::getLog()->error(__METHOD__ . ' create socket failed', [
                    'address'   => $address,
                    'errNumber' => $errNumber,
                    'errMsg'    => $errorMsg,
                ]);
                continue;
            }
            $clientArray[$address] = $client;
            stream_set_timeout($clientArray[$address], 0, 100000);
            fwrite($clientArray[$address], $buffer);
            stream_set_blocking($clientArray[$address], 0);
            $sockToAddress[(int) $client] = $address;
        }
        $read = $clientArray;
        $write = $except = $readBuffer = [];
        $timeStart = microtime(true);
        $timeout = 0.99;
        // 轮询处理数据
        while (count($read) > 0)
        {
            if (@stream_select($read, $write, $except, 0, 200000))
            {
                foreach ($read as $socket)
                {
                    $address = $sockToAddress[(int) $socket];
                    $buf = fread($socket, 8192);
                    if ( ! $buf)
                    {
                        if (feof($socket))
                        {
                            unset($clientArray[$address]);
                        }
                        continue;
                    }
                    if ( ! isset($readBuffer[$address]))
                    {
                        $readBuffer[$address] = $buf;
                    }
                    else
                    {
                        $readBuffer[$address] .= $buf;
                    }
                    // 数据接收完毕
                    if (($len = strlen($readBuffer[$address])) && $readBuffer[$address][$len - 1] === "\n")
                    {
                        unset($clientArray[$address]);
                    }
                }
            }
            // 超时了
            if (microtime(true) - $timeStart > $timeout)
            {
                break;
            }
            $read = $clientArray;
        }

        foreach ($readBuffer as $address => $buf)
        {
            $temp = explode(':', $address);
            $ip = array_shift($temp);
            Cache::$lastSuccessIpArray[$ip] = $ip;
        }

        Base::getLog()->info(__METHOD__ . ' Cache::$lastFailedIpArray', Cache::$lastFailedIpArray);
        Base::getLog()->info(__METHOD__ . ' $ipList', $ipList);
        Cache::$lastFailedIpArray = array_diff($ipList, Cache::$lastSuccessIpArray);

        ksort($readBuffer);

        return $readBuffer;
    }

    /**
     * 准备module参数，并读取数据
     *
     * @param string $module
     * @param string $interface
     * @param mixed  $date
     */
    public static function multiRequestStAndModules($module, $interface, $date)
    {
        Base::getLog()->info(__METHOD__ . ' calling multiRequestStAndModules - start');
        Cache::$statDataCache['statistic'] = '';

        $buffer = [
            'cmd'       => 'get_statistic',
            'module'    => $module,
            'interface' => $interface,
            'date'      => $date,
        ];
        Base::getLog()->info(__METHOD__ . ' prepare buffer', $buffer);
        $buffer = json_encode($buffer) . "\n";
        $ipList = ( ! empty($_GET['ip']) && is_array($_GET['ip'])) ? $_GET['ip'] : Cache::$serverIpList;
        $requestBufferArray = [];
        $port = Config::load('statServer')->get('providerPort');
        foreach ($ipList as $ip)
        {
            $requestBufferArray["$ip:$port"] = $buffer;
        }

        $readBufferArray = self::multiRequest($requestBufferArray);
        Base::getLog()->info(__METHOD__ . ' receive remote buffer', $readBufferArray);

        foreach ($readBufferArray as $address => $buf)
        {
            $temp = explode(':', $address);
            $ip = array_shift($temp);

            $bodyData = json_decode(trim($buf), true);
            $statData = Arr::get($bodyData, 'statistic', '');
            $modulesData = Arr::get($bodyData, 'modules', []);

            // 整理modules
            foreach ($modulesData as $mod => $interfaces)
            {
                if ( ! isset(Cache::$modulesDataCache[$mod]))
                {
                    Cache::$modulesDataCache[$mod] = [];
                }
                foreach ($interfaces as $if)
                {
                    Cache::$modulesDataCache[$mod][$if] = $if;
                }
            }
            Cache::$statDataCache['statistic'][$ip] = $statData;
        }

        Base::getLog()->info(__METHOD__ . ' calling multiRequestStAndModules - end');
    }

    /**
     * 格式化日志记录
     *
     * @param string $str
     * @param string $date
     * @param array  $codeMap
     * @return array
     */
    public static function formatStatLog($str, $date, &$codeMap)
    {
        // time:[success_count:xx,success_cost_time:xx,fail_count:xx,fail_cost_time:xx]
        $stData = $codeMap = [];
        $lines = explode("\n", $str);
        // 汇总计算
        foreach ($lines as $line)
        {
            // line = IP time success_count success_cost_time fail_count fail_cost_time code_json
            $lineData = explode("\t", $line);
            if ( ! isset($lineData[5]))
            {
                continue;
            }
            $timeLine = Arr::get($lineData, 1);
            $timeLine = intval(ceil($timeLine / 300) * 300);
            $successCount = Arr::get($lineData, 2);
            $successCostTime = Arr::get($lineData, 3);
            $failCount = Arr::get($lineData, 4);
            $failCostTime = Arr::get($lineData, 5);
            $codeMapList = json_decode($lineData[6], true);
            if ( ! isset($stData[$timeLine]))
            {
                $stData[$timeLine] = [
                    'success_count'     => 0,
                    'success_cost_time' => 0,
                    'fail_count'        => 0,
                    'fail_cost_time'    => 0,
                ];
            }
            $stData[$timeLine]['success_count'] += $successCount;
            $stData[$timeLine]['success_cost_time'] += $successCostTime;
            $stData[$timeLine]['fail_count'] += $failCount;
            $stData[$timeLine]['fail_cost_time'] += $failCostTime;

            if (is_array($codeMapList))
            {
                foreach ($codeMapList as $code => $count)
                {
                    if ( ! isset($codeMap[$code]))
                    {
                        $codeMap[$code] = 0;
                    }
                    $codeMap[$code] += $count;
                }
            }
        }
        // 按照时间排序
        ksort($stData);
        // time => [total_count:xx,success_count:xx,suc_avg_time:xx,fail_count:xx,fail_avg_time:xx,percent:xx]
        $data = [];
        // 计算成功率 耗时
        foreach ($stData as $timeLine => $item)
        {
            $data[$timeLine] = [
                'time'           => date('Y-m-d H:i:s', $timeLine),
                'total_count'    => $item['success_count'] + $item['fail_count'],
                'total_avg_time' => $item['success_count'] + $item['fail_count'] == 0 ? 0 : round(($item['success_cost_time'] + $item['fail_cost_time']) / ($item['success_count'] + $item['fail_count']), 6),
                'success_count'  => $item['success_count'],
                'suc_avg_time'   => $item['success_count'] == 0 ? $item['success_count'] : round($item['success_cost_time'] / $item['success_count'], 6),
                'fail_count'     => $item['fail_count'],
                'fail_avg_time'  => $item['fail_count'] == 0 ? 0 : round($item['fail_cost_time'] / $item['fail_count'], 6),
                'percent'        => $item['success_count'] + $item['fail_count'] == 0 ? 0 : round(($item['success_count'] * 100 / ($item['success_count'] + $item['fail_count'])), 4),
            ];
        }
        $timePoint = strtotime($date);
        for ($i = 0; $i < 288; $i++)
        {
            $data[$timePoint] = isset($data[$timePoint]) ? $data[$timePoint] : [
                'time'           => date('Y-m-d H:i:s', $timePoint),
                'total_count'    => 0,
                'total_avg_time' => 0,
                'success_count'  => 0,
                'suc_avg_time'   => 0,
                'fail_count'     => 0,
                'fail_avg_time'  => 0,
                'percent'        => 100,
            ];
            $timePoint += 300;
        }
        ksort($data);
        return $data;
    }
}
