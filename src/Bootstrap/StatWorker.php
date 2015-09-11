<?php

namespace stat\Bootstrap;

use stat\StatServer;
use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Base\Helper\Arr;
use tourze\Server\Worker;
use Workerman\Connection\ConnectionInterface;
use Workerman\Lib\Timer;

/**
 * Worker处理
 *
 * @package stat\Bootstrap
 */
class StatWorker extends Worker
{
    /**
     *  最大日志buffer，大于这个值就写磁盘
     *
     * @var integer
     */
    const MAX_LOG_BUFFER_SIZE = 1024000;

    /**
     * 多长时间写一次数据到磁盘
     *
     * @var integer
     */
    const WRITE_PERIOD_LENGTH = 60;

    /**
     * 多长时间清理一次老的磁盘数据
     *
     * @var integer
     */
    const CLEAR_PERIOD_LENGTH = 86400;

    /**
     * 数据多长时间过期
     *
     * @var integer
     */
    const EXPIRED_TIME = 1296000;

    /**
     * 统计数据
     * ip=>modid=>interface=>['code'=>[xx=>count,xx=>count],'success_cost_time'=>xx,'fail_cost_time'=>xx, 'success_count'=>xx, 'fail_count'=>xx]
     *
     * @var array
     */
    protected $statData = [];

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
     * @var resource 提供统计查询的socket
     */
    protected $providerSocket = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->onWorkerStart = [$this, 'onStart'];
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
     * 业务处理
     *
     * @see Man\Core.SocketWorker::dealProcess()
     * @param ConnectionInterface $connection
     * @param array               $data
     */
    public function onMessage($connection, $data)
    {
        Base::getLog()->info(__METHOD__ . ' receive data', $data);

        // 解码
        $module = Arr::get($data, 'module');
        $interface = Arr::get($data, 'interface');
        $costTime = Arr::get($data, 'cost_time');
        $success = Arr::get($data, 'success');
        $time = Arr::get($data, 'time');
        $code = Arr::get($data, 'code');
        $msg = str_replace("\n", "<br>", Arr::get($data, 'msg'));
        $ip = $connection->getRemoteIp();

        // 模块接口统计
        $this->collectStatistics($module, $interface, $costTime, $success, $ip, $code, $msg);
        // 全局统计
        $this->collectStatistics('WorkerMan', 'Statistics', $costTime, $success, $ip, $code, $msg);

        // 失败记录日志
        if ( ! $success)
        {
            $this->logBuffer .= date('Y-m-d H:i:s', $time)
                . "\t"
                . $ip
                . "\t"
                . $module
                . "::"
                . $interface
                . "\t"
                . "code:$code"
                . "\t"
                . "msg:$msg"
                . "\n";
            if (strlen($this->logBuffer) >= self::MAX_LOG_BUFFER_SIZE)
            {
                $this->writeLogToDisk();
            }
        }
    }

    /**
     * 收集统计数据
     *
     * @param string $module
     * @param string $interface
     * @param float  $costTime
     * @param int    $success
     * @param string $ip
     * @param int    $code
     * @param string $msg
     * @return void
     */
    protected function collectStatistics($module, $interface, $costTime, $success, $ip, $code, $msg)
    {
        // 统计相关信息
        if ( ! isset($this->statData[$ip]))
        {
            $this->statData[$ip] = [];
        }
        if ( ! isset($this->statData[$ip][$module]))
        {
            $this->statData[$ip][$module] = [];
        }
        if ( ! isset($this->statData[$ip][$module][$interface]))
        {
            $this->statData[$ip][$module][$interface] = ['code'              => [],
                                                         'success_cost_time' => 0,
                                                         'fail_cost_time'    => 0,
                                                         'success_count'     => 0,
                                                         'fail_count'        => 0];
        }
        if ( ! isset($this->statData[$ip][$module][$interface]['code'][$code]))
        {
            $this->statData[$ip][$module][$interface]['code'][$code] = 0;
        }
        $this->statData[$ip][$module][$interface]['code'][$code]++;
        if ($success)
        {
            $this->statData[$ip][$module][$interface]['success_cost_time'] += $costTime;
            $this->statData[$ip][$module][$interface]['success_count']++;
        }
        else
        {
            $this->statData[$ip][$module][$interface]['fail_cost_time'] += $costTime;
            $this->statData[$ip][$module][$interface]['fail_count']++;
        }
    }

    /**
     * 将统计数据写入磁盘
     *
     * @return void
     */
    public function writeStatToDisk()
    {
        $time = time();
        // 循环将每个ip的统计数据写入磁盘
        foreach ($this->statData as $ip => $modData)
        {
            foreach ($modData as $module => $items)
            {
                // 文件夹不存在则创建一个
                $file_dir = Config::load('statServer')->get('dataPath') . $this->statDir . $module;
                if ( ! is_dir($file_dir))
                {
                    umask(0);
                    mkdir($file_dir, 0777, true);
                }
                // 依次写入磁盘
                foreach ($items as $interface => $data)
                {
                    file_put_contents($file_dir . "/{$interface}." . date('Y-m-d'), "$ip\t$time\t{$data['success_count']}\t{$data['success_cost_time']}\t{$data['fail_count']}\t{$data['fail_cost_time']}\t" . json_encode($data['code']) . "\n", FILE_APPEND | LOCK_EX);
                }
            }
        }
        // 清空统计
        $this->statData = [];
    }

    /**
     * 将日志数据写入磁盘
     *
     * @return void
     */
    public function writeLogToDisk()
    {
        // 没有统计数据则返回
        if (empty($this->logBuffer))
        {
            return;
        }
        // 写入磁盘
        file_put_contents(Config::load('statServer')->get('dataPath') . $this->logDir . date('Y-m-d'), $this->logBuffer, FILE_APPEND | LOCK_EX);
        $this->logBuffer = '';
    }

    /**
     * 初始化
     * 统计目录检查
     * 初始化任务
     *
     * @see Man\Core.SocketWorker::onStart()
     */
    protected function onStart()
    {
        // 初始化目录
        umask(0);

        // 保证存放数据的目录存在和可写
        $storageDir = Config::load('statServer')->get('dataPath') . $this->statDir;
        if ( ! is_dir($storageDir))
        {
            mkdir($storageDir, 0777, true);
        }
        $logDir = Config::load('statServer')->get('dataPath') . $this->logDir;
        if ( ! is_dir($logDir))
        {
            mkdir($logDir, 0777, true);
        }

        // 定时保存统计数据
        Timer::add(self::WRITE_PERIOD_LENGTH, [$this, 'writeStatToDisk']);
        Timer::add(self::WRITE_PERIOD_LENGTH, [$this, 'writeLogToDisk']);

        // 定时清理不用的统计数据
        Timer::add(self::CLEAR_PERIOD_LENGTH, [$this, 'clearDisk'], [$storageDir, self::EXPIRED_TIME]);
        Timer::add(self::CLEAR_PERIOD_LENGTH, [$this, 'clearDisk'], [$logDir, self::EXPIRED_TIME]);

    }

    /**
     * 进程停止时需要将数据写入磁盘
     *
     * @see Man\Core.SocketWorker::onStop()
     */
    protected function onStop()
    {
        $this->writeLogToDisk();
        $this->writeStatToDisk();
    }

    /**
     * 清除磁盘数据
     *
     * @param string $file
     * @param int    $expiredTime
     */
    public function clearDisk($file = null, $expiredTime = 86400)
    {
        $time_now = time();
        if (is_file($file))
        {
            $mTime = filemtime($file);
            if ( ! $mTime)
            {
                print("filemtime $file fail");
                return;
            }
            if ($time_now - $mTime > $expiredTime)
            {
                unlink($file);
            }
            return;
        }
        foreach (glob($file . "/*") as $file_name)
        {
            $this->clearDisk($file_name, $expiredTime);
        }
    }
} 
