<?php

namespace stat\Controller;

use stat\Web;
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
        Web::checkAuth();
    }

}
