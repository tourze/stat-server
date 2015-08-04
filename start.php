<?php

use stat\Bootstrap\StatProvider;
use stat\Bootstrap\StatWorker;
use Workerman\Worker;
use Workerman\WebServer;

require 'bootstrap.php';

// StatProvider
$statProvider = new StatProvider("Text://0.0.0.0:55858");
$statProvider->name = 'StatProvider';

// StatWorker
$statWorker = new StatWorker("Statistic://0.0.0.0:55656");
$statWorker->transport = 'udp';
$statWorker->name = 'StatWorker';

// Web服务，用于为管理员提供查看数据的前端
$statWeb = new WebServer("http://0.0.0.0:55757");
$statWeb->name = 'StatWeb';
$statWeb->addRoot('stat.tourze.com', ROOT_PATH . 'web');

// 接收UDP广播，用于发现内网中其他其他的统计服务
$udpFinder = new Worker("Text://0.0.0.0:55858");
$udpFinder->name = 'StatFinder';
$udpFinder->transport = 'udp';
/**
 * @param \Workerman\Connection\ConnectionInterface $connection
 * @param $data
 * @return bool
 */
$udpFinder->onMessage = function ($connection, $data)
{
    $data = json_decode($data, true);
    if (empty($data))
    {
        return false;
    }

    // 无法解析的包
    if (empty($data['cmd']) || $data['cmd'] != 'REPORT_IP')
    {
        return false;
    }

    return $connection->send(json_encode(['result' => 'ok']));
};

// 运行所有服务
Worker::runAll();
