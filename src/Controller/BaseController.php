<?php

namespace tourze\StatServer\Controller;

use tourze\Controller\TemplateController;

/**
 * 基础的模板控制器
 *
 * @package tourze\StatServer\Controller
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
        return true;
    }
}
