<?php

use tourze\Base\Base;
use tourze\Bootstrap\Bootstrap;
use tourze\Flow\Flow;

require '../bootstrap.php';

/**
 * SDK启动
 */
$app = Base::instance();

// 主工作流
$flow = Flow::instance('sdk');
$flow->contexts = [
    'app'     => $app,
];
$flow->layers = Bootstrap::$layers;
$flow->start();
