<?php

use tourze\Base\Config;
use tourze\Route\Route;
use tourze\StatServer\Cache;
use tourze\View\View;

if (is_file(__DIR__ . 'vendor/autoload.php'))
{
    require_once 'vendor/autoload.php';
}

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

Cache::$serverIpList = Config::load('statServer')->get('serverIpList');

// 指定控制器命名空间
Route::$defaultNamespace = '\\tourze\\StatServer\\Controller\\';

Route::set('stat-web', '(<controller>(/<action>(/<id>)))')
    ->defaults([
        'controller' => 'Main',
        'action'     => 'index',
    ]);

// 创建类别名
@class_alias('\tourze\StatServer\Protocol\Statistic', '\Workerman\Protocols\Statistic');
