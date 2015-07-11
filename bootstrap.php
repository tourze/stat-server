<?php

use tourze\Tourze\Asset;

require 'vendor/autoload.php';

ini_set('display_errors', 'on');

defined('ROOT_PATH') || define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

Asset::$assetHost = 'http://local.asset.tourze.com/';
