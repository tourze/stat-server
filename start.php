<?php

use tourze\Server\Worker;

// 检查扩展
if ( ! extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}
if ( ! extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

require __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

Worker::load('stat-web');
Worker::load('stat-provider');
Worker::load('stat-worker');
Worker::load('stat-finder');
Worker::runAll();
