<?php

use stat\Cache;
use tourze\Base\Config;
use tourze\Tourze\Asset;
use tourze\View\View;

require 'vendor/autoload.php';

ini_set('display_errors', 'on');

if ( ! defined('ROOT_PATH'))
{
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// 检查扩展
if ( ! extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}
if ( ! extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

Asset::$assetHost = 'http://local.asset.tourze.com/';

Config::addPath(ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
View::addPath(ROOT_PATH . 'view' . DIRECTORY_SEPARATOR);

Cache::$ServerIpList = Config::load('statServer')->get('serverIpList');

// 创建类别名
@class_alias('\stat\Service\Protocol\Statistic', '\Workerman\Protocols\Statistic');
