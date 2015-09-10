<?php

namespace stat\Bootstrap;

use stat\StatServer;
use tourze\Base\Config;
use tourze\Base\Helper\Arr;
use tourze\Server\Worker;
use Workerman\Connection\ConnectionInterface;

/**
 * 数据提供者
 *
 * @package stat\Bootstrap
 */
class StatProvider extends Worker
{
    /**
     * @var int 最大日志buffer，大于这个值就写磁盘
     */
    const MAX_LOG_BUFFER_SIZE = 1024000;

    /**
     * @var int 多长时间写一次数据到磁盘
     */
    const WRITE_PERIOD_LENGTH = 60;

    /**
     * @var int 多长时间清理一次老的磁盘数据
     */
    const CLEAR_PERIOD_LENGTH = 604800;

    /**
     * @var int 数据多长时间过期
     */
    const EXPIRED_TIME = 1296000;

    /**
     * 统计数据
     * ip=>modid=>interface=>['code'=>[xx=>count,xx=>count],'success_cost_time'=>xx,'fail_cost_time'=>xx, 'success_count'=>xx, 'fail_count'=>xx]
     *
     * @var array
     */
    protected $statisticData = [];

    /**
     * @var string 日志的buffer
     */
    protected $logBuffer = '';

    /**
     * @var string 放统计数据的目录
     */
    protected $statDir = '';

    /**
     * @var string 存放统计日志的目录
     */
    protected $logDir = '';

    /**
     * 用于接收广播的udp socket
     *
     * @var resource
     */
    protected $broadcastSocket = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->onMessage = [$this, 'onMessage'];
        if ( ! $this->statDir)
        {
            $this->statDir = StatServer::$statDir;
        }
        if ( ! $this->logDir)
        {
            $this->logDir = StatServer::$logDir;
        }
    }

    /**
     * 处理请求统计
     *
     * @param ConnectionInterface $connection
     * @param string              $receiveBuffer
     */
    public function onMessage($connection, $receiveBuffer)
    {
        $requestData = json_decode(trim($receiveBuffer), true);

        $module = Arr::get($requestData, 'module');
        $interface = Arr::get($requestData, 'interface');
        $cmd = Arr::get($requestData, 'cmd');

        $start_time = Arr::get($requestData, 'start_time', '');
        $end_time = Arr::get($requestData, 'end_time', '');
        $date = Arr::get($requestData, 'date', '');
        $code = Arr::get($requestData, 'code', '');
        $msg = Arr::get($requestData, 'msg', '');
        $offset = Arr::get($requestData, 'offset', '');
        $count = Arr::get($requestData, 'count', 10);

        // 根据发送过来的cmd参数
        switch ($cmd)
        {
            case 'get_statistic':
                $buffer = json_encode(['modules' => $this->getModules($module), 'statistic' => $this->getStatistic($date, $module, $interface)]) . "\n";
                $connection->send($buffer);
                break;
            case 'get_log':
                $buffer = json_encode($this->getStasticLog($module, $interface, $start_time, $end_time, $code, $msg, $offset, $count)) . "\n";
                $connection->send($buffer);
                break;
            default :
                $connection->send('pack err');
        }
    }

    /**
     * 获取模块
     *
     * @param string $currentModule
     * @return array
     */
    public function getModules($currentModule = '')
    {
        $storageDir = Config::load('statServer')->get('dataPath') . $this->statDir;

        $result = [];
        foreach (glob($storageDir . "/*", GLOB_ONLYDIR) as $moduleFile)
        {
            $tmp = explode("/", $moduleFile);
            $module = end($tmp);
            $result[$module] = [];

            // 最多只支持两层
            if ($currentModule == $module)
            {
                $storageDir = $storageDir . $currentModule . '/';
                $interfaceList = [];
                foreach (glob($storageDir . "*") as $file)
                {
                    if (is_dir($file))
                    {
                        continue;
                    }
                    list($interface, $date) = explode(".", basename($file));
                    $interfaceList[$interface] = $interface;
                }
                $result[$module] = $interfaceList;
            }
        }
        return $result;
    }

    /**
     * 获得指定模块的统计数据
     *
     * @param string $module
     * @param string $interface
     * @param int    $date
     * @return bool/string
     */
    protected function getStatistic($date, $module, $interface)
    {
        if (empty($module) || empty($interface))
        {
            return '';
        }
        // log文件
        $logFile = Config::load('statServer')->get('dataPath') . $this->statDir . "{$module}/{$interface}.{$date}";

        $handle = @fopen($logFile, 'r');
        if ( ! $handle)
        {
            return '';
        }

        // 预处理统计数据，每5分钟一行
        // [time=>[ip=>['success_count'=>xx, 'success_cost_time'=>xx, 'fail_count'=>xx, 'fail_cost_time'=>xx, 'code_map'=>[code=>count, ..], ..], ..]
        $statData = [];
        while ( ! feof($handle))
        {
            $line = fgets($handle, 4096);
            if ($line)
            {
                $explode = explode("\t", $line);
                if (count($explode) < 7)
                {
                    continue;
                }
                list($ip, $time, $successCount, $successCostTime, $failCount, $failCostTime, $codeMap) = $explode;
                $time = intval(ceil($time / 300) * 300);
                if ( ! isset($statData[$time]))
                {
                    $statData[$time] = [];
                }
                if ( ! isset($statData[$time][$ip]))
                {
                    $statData[$time][$ip] = [
                        'success_count'     => 0,
                        'success_cost_time' => 0,
                        'fail_count'        => 0,
                        'fail_cost_time'    => 0,
                        'code_map'          => [],
                    ];
                }
                $statData[$time][$ip]['success_count'] += $successCount;
                $statData[$time][$ip]['success_cost_time'] += round($successCostTime, 5);
                $statData[$time][$ip]['fail_count'] += $failCount;
                $statData[$time][$ip]['fail_cost_time'] += round($failCostTime, 5);
                $codeMap = json_decode(trim($codeMap), true);
                if ($codeMap && is_array($codeMap))
                {
                    foreach ($codeMap as $code => $count)
                    {
                        if ( ! isset($statData[$time][$ip]['code_map'][$code]))
                        {
                            $statData[$time][$ip]['code_map'][$code] = 0;
                        }
                        $statData[$time][$ip]['code_map'][$code] += $count;
                    }
                }
            } // end if
        } // end while

        fclose($handle);
        ksort($statData);

        // 整理数据
        $result = '';
        foreach ($statData as $time => $items)
        {
            foreach ($items as $ip => $item)
            {
                $result .= "$ip\t$time\t{$item['success_count']}\t{$item['success_cost_time']}\t{$item['fail_count']}\t{$item['fail_cost_time']}\t" . json_encode($item['code_map']) . "\n";
            }
        }
        return $result;
    }

    /**
     * 获取指定时间段的日志
     *
     * @param string $module
     * @param string $interface
     * @param string $startTime
     * @param string $endTime
     * @param string $code
     * @param string $msg
     * @param string $offset
     * @param int    $count
     * @return array
     */
    protected function getStasticLog($module, $interface, $startTime = '', $endTime = '', $code = '', $msg = '', $offset = '', $count = 100)
    {
        // log文件
        $logFile = Config::load('statServer')->get('dataPath') . $this->logDir . (empty($startTime) ? date('Y-m-d') : date('Y-m-d', $startTime));
        if ( ! is_readable($logFile))
        {
            return ['offset' => 0, 'data' => ''];
        }
        // 读文件
        $h = fopen($logFile, 'r');

        // 如果有时间，则进行二分查找，加速查询
        if ($startTime && $offset == 0 && ($file_size = filesize($logFile)) > 1024000)
        {
            $offset = $this->binarySearch(0, $file_size, $startTime - 1, $h);
            $offset = $offset < 100000 ? 0 : $offset - 100000;
        }

        // 正则表达式
        $pattern = "/^([\d: \-]+)\t\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\t";

        if ($module && $module != 'WorkerMan')
        {
            $pattern .= $module . "::";
        }
        else
        {
            $pattern .= ".*::";
        }

        if ($interface && $module != 'WorkerMan')
        {
            $pattern .= $interface . "\t";
        }
        else
        {
            $pattern .= ".*\t";
        }

        if ($code !== '')
        {
            $pattern .= "code:$code\t";
        }
        else
        {
            $pattern .= "code:\d+\t";
        }

        if ($msg)
        {
            $pattern .= "msg:$msg";
        }

        $pattern .= '/';

        // 指定偏移位置
        if ($offset > 0)
        {
            fseek($h, (int) $offset - 1);
        }

        // 查找符合条件的数据
        $now_count = 0;
        $log_buffer = '';

        while (1)
        {
            if (feof($h))
            {
                break;
            }
            // 读1行
            $line = fgets($h);
            if (preg_match($pattern, $line, $match))
            {
                // 判断时间是否符合要求
                $time = strtotime($match[1]);
                if ($startTime)
                {
                    if ($time < $startTime)
                    {
                        continue;
                    }
                }
                if ($endTime)
                {
                    if ($time > $endTime)
                    {
                        break;
                    }
                }
                // 收集符合条件的log
                $log_buffer .= $line;
                if (++$now_count >= $count)
                {
                    break;
                }
            }
        }
        // 记录偏移位置
        $offset = ftell($h);
        return ['offset' => $offset, 'data' => $log_buffer];
    }

    /**
     * 日志二分查找法
     *
     * @param int      $startPoint
     * @param int      $endPoint
     * @param int      $time
     * @param resource $fd
     * @return int
     */
    protected function binarySearch($startPoint, $endPoint, $time, $fd)
    {
        if ($endPoint - $startPoint < 65535)
        {
            return $startPoint;
        }

        // 计算中点
        $mid_point = (int) (($endPoint + $startPoint) / 2);

        // 定位文件指针在中点
        fseek($fd, $mid_point - 1);

        // 读第一行
        $line = fgets($fd);
        if (feof($fd) || false === $line)
        {
            return $startPoint;
        }

        // 第一行可能数据不全，再读一行
        $line = fgets($fd);
        if (feof($fd) || false === $line || trim($line) == '')
        {
            return $startPoint;
        }

        // 判断是否越界
        $current_point = ftell($fd);
        if ($current_point >= $endPoint)
        {
            return $startPoint;
        }

        // 获得时间
        $tmp = explode("\t", $line);
        $tmp_time = strtotime($tmp[0]);

        // 判断时间，返回指针位置
        if ($tmp_time > $time)
        {
            return $this->binarySearch($startPoint, $current_point, $time, $fd);
        }
        elseif ($tmp_time < $time)
        {
            return $this->binarySearch($current_point, $endPoint, $time, $fd);
        }
        else
        {
            return $current_point;
        }
    }
} 
