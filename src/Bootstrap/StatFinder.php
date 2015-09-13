<?php

namespace tourze\StatServer\Bootstrap;

use tourze\Base\Base;
use tourze\Server\Worker;
use Workerman\Connection\ConnectionInterface;

/**
 * Class StatFinder
 *
 * @package tourze\StatServer\Bootstrap
 */
class StatFinder extends Worker
{

    /**
     * {@inheritdoc}
     */
    public $transport = 'udp';

    /**
     * {@inheritdoc}
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->onMessage = [$this, 'onMessage'];
    }

    /**
     * 处理请求统计
     *
     * @param ConnectionInterface $connection
     * @param string              $data
     * @return bool|void
     */
    public function onMessage($connection, $data)
    {
        Base::getLog()->info(__METHOD__ . ' handle message', $data);

        $data = json_decode($data, true);
        if (empty($data) || ! isset($data['cmd']))
        {
            Base::getLog()->info(__METHOD__ . ' no cmd param, or the data format is wrong', $data);
            return false;
        }

        // 无法解析的包
        if (empty($data['cmd']) || $data['cmd'] != 'REPORT_IP')
        {
            return false;
        }

        Base::getLog()->info(__METHOD__ . ' response ok');
        /** @var \Workerman\Connection\ConnectionInterface $connection */
        return $connection->send(json_encode(['result' => 'ok']));
    }
}
