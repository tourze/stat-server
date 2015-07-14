<?php

use tourze\Base\Config;

require '../bootstrap.php';

define('ST_ROOT', ROOT_PATH . 'Applications/Statistics');

// 覆盖配置文件
foreach(glob(Config::load('statServer')->get('configCachePath') . '*.php')  as $php_file)
{
    require_once $php_file;
}
