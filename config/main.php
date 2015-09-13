<?php

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
            'socketName'     => 'http://0.0.0.0:8088', // 默认监听8080端口
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
        ],
        // Finder 接收UDP广播，用于发现内网中其他其他的统计服务
        'stat-finder'   => [
            'socketName' => 'Text://0.0.0.0:55858',
            'initClass'  => '\stat\Bootstrap\StatFinder',
        ],
    ],

];
