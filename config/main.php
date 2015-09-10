<?php

use tourze\Base\Base;

return [

    'component' => [
        'http'    => [
            'class'  => 'tourze\Server\Component\Http',
            'params' => [
            ],
            'call'   => [
            ],
        ],
        'session' => [
            'class'  => 'tourze\Server\Component\Session',
            'params' => [
            ],
            'call'   => [
            ],
        ],
        'log'     => [
            'class'  => 'tourze\Server\Component\Log',
            'params' => [
            ],
            'call'   => [
            ],
        ],
    ],
    'server'    => [
        // web部分
        'stat-web'      => [
            'count'          => 4, // 打开进程数
            'user'           => '', // 使用什么用户打开
            'reloadable'     => true, // 是否支持平滑重启
            'socketName'     => 'http://0.0.0.0:8080', // 默认监听8080端口
            'contextOptions' => [], // 上下文选项
            'siteList'       => [
                'local.uc.tourze.com' => WEB_PATH,
            ],
            'rewrite'        => 'index.php',
        ],
        // Provider
        'stat-provider' => [
            'socketName' => 'Text://0.0.0.0:55858',
            'initClass'  => '\stat\Bootstrap\StatProvider',
        ],
        // Worker进程，接受用户提交请求
        'stat-worker'   => [
            'socketName' => 'Statistic://0.0.0.0:55656',
            'initClass'  => '\stat\Bootstrap\StatWorker',
            'transport'  => 'udp',
        ],
        // Finder 接收UDP广播，用于发现内网中其他其他的统计服务
        'stat-finder'   => [
            'socketName' => 'Text://0.0.0.0:55858',
            'transport'  => 'udp',
            'onMessage'  => function ($connection, $data)
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
            },
        ],
    ],

];
