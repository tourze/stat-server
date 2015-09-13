<?php

namespace tourze\StatServer\Controller;

use tourze\Base\Config;
use tourze\StatServer\Cache;

/**
 * 管理控制器
 *
 * @package tourze\StatServer\Controller
 */
class AdminController extends BaseController
{
    public function actionIndex()
    {
        $act = isset($_GET['act']) ? $_GET['act'] : 'home';
        $errorMsg = $noticeMsg = $successMsg = $ipListStr = '';
        $action = 'save-server-list';
        switch ($act)
        {
            case 'detect-server':
                // 创建udp socket
                $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
                $buffer = json_encode(['cmd' => 'REPORT_IP']) . "\n";
                // 广播
                socket_sendto($socket, $buffer, strlen($buffer), 0, '255.255.255.255', Config::load('statServer')->get('providerPort'));
                // 超时相关
                $time_start = microtime(true);
                $global_timeout = 1;
                $ipList = [];
                $recv_timeout = ['sec' => 0, 'usec' => 8000];
                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $recv_timeout);

                // 循环读数据
                while (microtime(true) - $time_start < $global_timeout)
                {
                    $buf = $host = $port = '';
                    if (@socket_recvfrom($socket, $buf, 65535, 0, $host, $port))
                    {
                        $ipList[$host] = $host;
                    }
                }

                // 过滤掉已经保存的ip
                $count = 0;
                foreach ($ipList as $ip)
                {
                    if ( ! isset(Cache::$serverIpList[$ip]))
                    {
                        $ipListStr .= $ip . "\r\n";
                        $count++;
                    }
                }
                $action = 'add-to-server-list';
                $noticeMsg = "探测到{$count}个新数据源";
                break;
            case 'add-to-server-list':
                if (empty($_POST['ip_list']))
                {
                    $errorMsg = "保存的ip列表为空";
                    break;
                }
                $ipList = explode("\n", $_POST['ip_list']);
                if ($ipList)
                {
                    foreach ($ipList as $ip)
                    {
                        $ip = trim($ip);
                        if (false !== ip2long($ip))
                        {
                            Cache::$serverIpList[$ip] = $ip;
                        }
                    }
                }
                $successMsg = "添加成功";
                foreach (Cache::$serverIpList as $ip)
                {
                    $ipListStr .= $ip . "\r\n";
                }
                break;
            case 'save-server-list':
                if (empty($_POST['ip_list']))
                {
                    $errorMsg = "保存的ip列表为空";
                    break;
                }
                Cache::$serverIpList = [];
                $ipList = explode("\n", $_POST['ip_list']);
                if ($ipList)
                {
                    foreach ($ipList as $ip)
                    {
                        $ip = trim($ip);
                        if (false !== ip2long($ip))
                        {
                            Cache::$serverIpList[$ip] = $ip;
                        }
                    }
                }
                $successMsg = "保存成功";
                foreach (Cache::$serverIpList as $ip)
                {
                    $ipListStr .= $ip . "\r\n";
                }
                break;
            default:
                foreach (Cache::$serverIpList as $ip)
                {
                    $ipListStr .= $ip . "\r\n";
                }
        }

        $this->template->set('admin/index', [
            'act'        => $act,
            'action'     => $action,
            'successMsg' => $successMsg,
            'noticeMsg'  => $noticeMsg,
            'errorMsg'   => $errorMsg,
            'ipListStr'  => $ipListStr,
        ]);
    }
}
