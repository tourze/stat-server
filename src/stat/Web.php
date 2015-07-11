<?php

namespace stat;

use Workerman\Protocols\Http;

class Web
{

    /**
     * 启动session，兼容fpm
     */
    public static function sessionStart()
    {
        if (defined('WORKERMAN_ROOT_DIR'))
        {
            return Http::sessionStart();
        }
        return session_start();
    }

    /**
     * 退出
     *
     * @param string $str
     */
    public static function _exit($str = '')
    {
        if (defined('WORKERMAN_ROOT_DIR'))
        {
            Http::end($str);
            return;
        }
        return exit($str);
    }

    /**
     * 检查是否登录
     */
    public static function checkAuth()
    {
        // 如果配置中管理员用户名密码为空则说明不用验证
        if (\Statistics\Config::$adminName == '' && \Statistics\Config::$adminPassword == '')
        {
            return true;
        }
        // 进入验证流程
        self::sessionStart();
        if ( ! isset($_SESSION['admin']))
        {
            if ( ! isset($_POST['admin_name']) || ! isset($_POST['admin_password']))
            {
                include ROOT_PATH . 'view/login.tpl.php';
                self::_exit();
            }
            else
            {
                $admin_name = $_POST['admin_name'];
                $admin_password = $_POST['admin_password'];
                if ($admin_name != \Statistics\Config::$adminName || $admin_password != \Statistics\Config::$adminPassword)
                {
                    $msg = "用户名或者密码不正确";
                    include ROOT_PATH . 'view/login.tpl.php';
                    self::_exit();
                }
                $_SESSION['admin'] = $admin_name;
            }
        }
        return true;
    }

}
