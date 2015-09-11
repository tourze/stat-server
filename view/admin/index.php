<?php

use tourze\Route\Route;

/** @var string $act */
/** @var string $action */
/** @var string $successMsg */
/** @var string $noticeMsg */
/** @var string $errorMsg */
/** @var string $ipListStr */

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
                <li>
                    <a href="<?php echo Route::url('stat-web', ['controller' => 'Logger']) ?>">日志</a>
                </li>
                <li class="dropdown pull-right">
                    <a href="#" data-toggle="dropdown" class="dropdown-toggle">其它<strong class="caret"></strong></a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php
                            echo Route::url('stat-web', ['controller' => 'Admin', 'action' => 'detect-server'])
                            ?>">探测数据源</a>
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
            <ul class="breadcrumb">
                <li>
                    <a href="<?php echo Route::url('stat-web', ['controller' => 'Admin']) ?>?<?php echo $act == 'detect-server' ? '&act=detect-server' : ''; ?>"><?php echo $act == 'detect-server' ? '数据源探测' : '数据源管理'; ?></a>
                    <span class="divider">/</span>
                </li>
                <li class="active">
                    <?php if ($act == 'home')
                    {
                        echo '数据源列表';
                    }
                    elseif ($act == 'detect-server')
                    {
                        echo '探测结果';
                    }
                    elseif ($act == 'add-to-server-list')
                    {
                        echo '添加结果';
                    }
                    elseif ($act == 'save-server-list')
                    {
                        echo '保存结果';
                    } ?>
                </li>
            </ul>
            <?php if ($successMsg)
            { ?>
                <div class="alert alert-dismissable alert-success">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><?php echo $successMsg; ?></strong>
                </div>
            <?php }
            elseif ($errorMsg)
            { ?>
                <div class="alert alert-dismissable alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><?php echo $errorMsg; ?></strong>
                </div>
            <?php }
            elseif ($noticeMsg)
            { ?>
                <div class="alert alert-dismissable alert-info">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><?php echo $noticeMsg; ?></strong>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="row clearfix">
        <div class="col-md-3 column">
        </div>
        <div class="col-md-6 column">
            <?php if ($act != 'add-to-server-list')
            { ?>
                <form
                    action="<?php echo Route::url('stat-web', ['controller' => 'Admin']) ?>?act=<?php echo $action; ?>"
                    method="post">
                    <div class="form-group">
                        <textarea rows="22" cols="30" name="ip_list"><?php echo $ipListStr; ?></textarea>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-1 col-sm-11">
                            <button type="submit"
                                    class="btn btn-default"><?php echo $act == 'detect-server' ? '添加到数据源列表' : '保存' ?></button>
                        </div>
                    </div>
                </form>
            <?php }
            else
            { ?>
                <a type="button" class="btn btn-default" href="/">返回主页</a>&nbsp;<a type="button" class="btn btn-default"
                                                                                   href="<?php echo Route::url('stat-web', ['controller' => 'Admin']) ?>">继续添加</a>
            <?php } ?>
        </div>
        <div class="col-md-3 column">
        </div>
    </div>
</div>
