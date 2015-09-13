# 统计模块

为多业务提供数据收集、处理和展示的功能。可部署多套。
本系统是在workerman/statistic项目的基础上二次开发的，感谢walkor提供的好项目！

> 目前版本未稳定，建议谨慎在生产环境使用。

NOTICE:

*  管理员用户名密码默认都为空，即不需要登录就可以查看监控数据
*  如果需要登录验证，在config/statServer.php里面设置管理员密码

## 安装说明

如果要使用在正式环境，建议使用下面命令来安装：

    composer create-project tourze/stat-server stat-server-path

启动：

    php start.php start

## 待开发

1. 完善功能，做code review
2. 完善接口功能
3. 撰写完整的单元测试
4. 跟ELK等的整合
