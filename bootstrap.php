<?php

use stat\Cache;
use tourze\Base\Config;
use tourze\Route\Route;
use tourze\View\View;

require 'vendor/autoload.php';

ini_set('display_errors', 'on');

if ( ! defined('ROOT_PATH'))
{
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

if ( ! defined('WEB_PATH'))
{
    define('WEB_PATH', ROOT_PATH . 'web' . DIRECTORY_SEPARATOR);
}

Config::addPath(ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
View::addPath(ROOT_PATH . 'view' . DIRECTORY_SEPARATOR);

Cache::$ServerIpList = Config::load('statServer')->get('serverIpList');

// 指定控制器命名空间
Route::$defaultNamespace = '\\stat\\Controller\\';

// 创建类别名
@class_alias('\stat\Service\Protocol\Statistic', '\Workerman\Protocols\Statistic');
