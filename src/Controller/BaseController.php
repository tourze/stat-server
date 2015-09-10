<?php

namespace stat\Controller;

use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Controller\TemplateController;

/**
 * 基础的模板控制器
 *
 * @package stat\Controller
 */
abstract class BaseController extends TemplateController
{

    /**
     * {@inheritdoc}
     */
    public function before()
    {
        parent::before();
        // 检查是否登录
        $this->checkAuth();
    }

    /**
     * 检查是否登录
     */
    public function checkAuth()
    {
        // 如果配置中管理员用户名密码为空则说明不用验证
        if (Config::load('statServer')->get('adminName') == '' && Config::load('statServer')->get('adminPassword') == '')
        {
            return true;
        }

        if ( ! Base::getSession()->get('admin'))
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
                if ($admin_name != Config::load('statServer')->get('adminName') || $admin_password != Config::load('statServer')->get('adminPassword'))
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
