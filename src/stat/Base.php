<?php

namespace stat;

use tourze\Base\Config;

class Base
{

    /**
     * 批量请求
     *
     * @param array $request_buffer_array ['ip:port'=>req_buf, 'ip:port'=>req_buf, ...]
     * @return mixed multitype:unknown string
     */
    public static function multiRequest($request_buffer_array)
    {
        Cache::$lastSuccessIpArray = [];
        $clientArray = $sockToIP = $ipList = $sockToAddress = [];
        foreach ($request_buffer_array as $address => $buffer)
        {
            list($ip, $port) = explode(':', $address);
            $ipList[$ip] = $ip;
            $client = stream_socket_client("tcp://$address", $errno, $errmsg, 1);
            if ( ! $client)
            {
                continue;
            }
            $clientArray[$address] = $client;
            stream_set_timeout($clientArray[$address], 0, 100000);
            fwrite($clientArray[$address], $buffer);
            stream_set_blocking($clientArray[$address], 0);
            $sockToAddress[(int) $client] = $address;
        }
        $read = $clientArray;
        $write = $except = $read_buffer = [];
        $time_start = microtime(true);
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
                    if ( ! isset($read_buffer[$address]))
                    {
                        $read_buffer[$address] = $buf;
                    }
                    else
                    {
                        $read_buffer[$address] .= $buf;
                    }
                    // 数据接收完毕
                    if (($len = strlen($read_buffer[$address])) && $read_buffer[$address][$len - 1] === "\n")
                    {
                        unset($clientArray[$address]);
                    }
                }
            }
            // 超时了
            if (microtime(true) - $time_start > $timeout)
            {
                break;
            }
            $read = $clientArray;
        }

        foreach ($read_buffer as $address => $buf)
        {
            list($ip, $port) = explode(':', $address);
            Cache::$lastSuccessIpArray[$ip] = $ip;
        }

        Cache::$lastFailedIpArray = array_diff($ipList, Cache::$lastSuccessIpArray);

        ksort($read_buffer);

        return $read_buffer;
    }

    public static function multiRequestStAndModules($module, $interface, $date)
    {
        Cache::$statisticDataCache['statistic'] = '';
        $buffer = json_encode(['cmd' => 'get_statistic', 'module' => $module, 'interface' => $interface, 'date' => $date]) . "\n";
        $ipList = ( ! empty($_GET['ip']) && is_array($_GET['ip'])) ? $_GET['ip'] : Cache::$ServerIpList;
        $requestBufferArray = [];
        $port = Config::load('statServer')->get('providerPort');
        foreach ($ipList as $ip)
        {
            $requestBufferArray["$ip:$port"] = $buffer;
        }
        $readBufferArray = self::multiRequest($requestBufferArray);
        foreach ($readBufferArray as $address => $buf)
        {
            list($ip, $port) = explode(':', $address);
            $body_data = json_decode(trim($buf), true);
            $statistic_data = isset($body_data['statistic']) ? $body_data['statistic'] : '';
            $modules_data = isset($body_data['modules']) ? $body_data['modules'] : [];
            // 整理modules
            foreach ($modules_data as $mod => $interfaces)
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
            Cache::$statisticDataCache['statistic'][$ip] = $statistic_data;
        }
    }

    public static function formatSt($str, $date, &$code_map)
    {
        // time:[success_count:xx,success_cost_time:xx,fail_count:xx,fail_cost_time:xx]
        $stData = $code_map = [];
        $st_explode = explode("\n", $str);
        // 汇总计算
        foreach ($st_explode as $line)
        {
            // line = IP time success_count success_cost_time fail_count fail_cost_time code_json
            $line_data = explode("\t", $line);
            if ( ! isset($line_data[5]))
            {
                continue;
            }
            $timeLine = $line_data[1];
            $timeLine = intval(ceil($timeLine / 300) * 300);
            $success_count = $line_data[2];
            $success_cost_time = $line_data[3];
            $fail_count = $line_data[4];
            $fail_cost_time = $line_data[5];
            $tmp_code_map = json_decode($line_data[6], true);
            if ( ! isset($stData[$timeLine]))
            {
                $stData[$timeLine] = ['success_count' => 0, 'success_cost_time' => 0, 'fail_count' => 0, 'fail_cost_time' => 0];
            }
            $stData[$timeLine]['success_count'] += $success_count;
            $stData[$timeLine]['success_cost_time'] += $success_cost_time;
            $stData[$timeLine]['fail_count'] += $fail_count;
            $stData[$timeLine]['fail_cost_time'] += $fail_cost_time;

            if (is_array($tmp_code_map))
            {
                foreach ($tmp_code_map as $code => $count)
                {
                    if ( ! isset($code_map[$code]))
                    {
                        $code_map[$code] = 0;
                    }
                    $code_map[$code] += $count;
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
                'success_count'      => $item['success_count'],
                'suc_avg_time'   => $item['success_count'] == 0 ? $item['success_count'] : round($item['success_cost_time'] / $item['success_count'], 6),
                'fail_count'     => $item['fail_count'],
                'fail_avg_time'  => $item['fail_count'] == 0 ? 0 : round($item['fail_cost_time'] / $item['fail_count'], 6),
                'precent'        => $item['success_count'] + $item['fail_count'] == 0 ? 0 : round(($item['success_count'] * 100 / ($item['success_count'] + $item['fail_count'])), 4),
            ];
        }
        $time_point = strtotime($date);
        for ($i = 0; $i < 288; $i++)
        {
            $data[$time_point] = isset($data[$time_point]) ? $data[$time_point] :
                [
                    'time'           => date('Y-m-d H:i:s', $time_point),
                    'total_count'    => 0,
                    'total_avg_time' => 0,
                    'success_count'      => 0,
                    'suc_avg_time'   => 0,
                    'fail_count'     => 0,
                    'fail_avg_time'  => 0,
                    'precent'        => 100,
                ];
            $time_point += 300;
        }
        ksort($data);
        return $data;
    }
}
