<?php

use tourze\Base\Config;

require '../bootstrap.php';

// 覆盖配置文件
foreach(glob(Config::load('statServer')->get('configCachePath') . '*.php')  as $php_file)
{
    require_once $php_file;
}
