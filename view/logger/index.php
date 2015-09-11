<?php

use tourze\Route\Route;

/** @var string $logStr */

?>
<div class="container">
    <div class="row clearfix">
        <div class="col-md-12 column">
            <ul class="nav nav-tabs">
                <li>
                    <a href="/">概述</a>
                </li>
                <li>
                    <a href="<?php echo Route::url('stat-web', ['controller' => 'Statistic']) ?>">监控</a>
                </li>
                <li class="active">
                    <a href="<?php echo Route::url('stat-web', ['controller' => 'Logger']) ?>">日志</a>
                </li>
                <li class="dropdown pull-right">
                    <a href="#" data-toggle="dropdown" class="dropdown-toggle">其它<strong class="caret"></strong></a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo Route::url('stat-web', ['controller' => 'Admin', 'action' => 'detect-server']) ?>">探测数据源</a>
                        </li>
                        <li>
                            <a href="<?php echo Route::url('stat-web', ['controller' => 'Admin']) ?>">数据源管理</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="row clearfix">
        <div class="col-md-12 column">
            <?php echo $logStr; ?>
        </div>
    </div>
</div>
