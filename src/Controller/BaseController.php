<?php

namespace stat\Controller;

use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Controller\TemplateController;
use tourze\View\View;

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
        // 检查是否登录
        $this->checkAuth();
        parent::before();
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
                $this->response->body = View::factory('login');
                $this->autoRender = false;
                $this->break = true;
                return false;
            }
            else
            {
                $adminName = $_POST['admin_name'];
                $adminPass = $_POST['admin_password'];
                if ($adminName != Config::load('statServer')->get('adminName') || $adminPass != Config::load('statServer')->get('adminPassword'))
                {
                    $this->response->body = View::factory('login', [
                        'msg' => '用户名或者密码不正确',
                    ]);
                    $this->autoRender = false;
                    $this->break = true;
                    return false;
                }
                else
                {
                    Base::getSession()->set('admin', $adminName);
                }
            }
        }
        return true;
    }
}
