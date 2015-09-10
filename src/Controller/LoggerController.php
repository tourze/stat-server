<?php

namespace stat\Controller;

use stat\StatServer;
use stat\Cache;
use tourze\Base\Config;
use tourze\Route\Route;
use tourze\View\View;

/**
 * 日志查看控制器
 *
 * @package stat\Controller
 */
class LoggerController extends BaseController
{

    /**
     * 缺省动作
     */
    public function actionIndex()
    {
        $module = $this->request->query('module');
        $interface = $this->request->query('interface');
        $start_time = $this->request->query('start_time');
        $offset = $this->request->query('offset');
        $count = $this->request->query('count');

        $module_str = '';
        foreach (Cache::$modulesDataCache as $mod => $interfaces)
        {
            if ($mod == 'WorkerMan')
            {
                continue;
            }
            $module_str .= '<li><a href="' . Route::url('stat-web', ['controller' => 'Statistic']) . '?module=' . $mod . '">' . $mod . '</a></li>';
            if ($module == $mod)
            {
                foreach ($interfaces as $if)
                {
                    $module_str .= '<li>&nbsp;&nbsp;<a href="' . Route::url('stat-web', ['controller' => 'Statistic']) . '?module=' . $mod . '&interface=' . $if . '">' . $if . '</a></li>';
                }
            }
        }

        $logDataArray = $this->getStasticLog($module, $interface, $start_time, $offset, $count);
        unset($_GET['fn'], $_GET['ip'], $_GET['offset']);
        $logStr = '';
        foreach ($logDataArray as $address => $log_data)
        {
            list($ip, $port) = explode(':', $address);
            $logStr .= $log_data['data'];
            $_GET['ip'][] = $ip;
            $_GET['offset'][] = $log_data['offset'];
        }
        $logStr = nl2br(str_replace("\n", "\n\n", $logStr));
        $nextPageUrl = http_build_query($_GET);
        $logStr .= '</br><center><a href="' . Route::url('stat-web', ['controller' => 'Logger']) . '?' . $nextPageUrl . '">下一页</a></center>';

        $this->template->set('content', View::factory('logger/index', [
            'logStr' => $logStr,
        ]));
    }

    public function getStasticLog($module, $interface, $start_time, $offset = '', $count = 10)
    {
        $ipList = ( ! empty($_GET['ip']) && is_array($_GET['ip'])) ? $_GET['ip'] : Cache::$serverIpList;
        $offset_list = ( ! empty($_GET['offset']) && is_array($_GET['offset'])) ? $_GET['offset'] : [];
        $port = Config::load('statServer')->get('providerPort');
        $requestBufferArray = [];
        foreach ($ipList as $key => $ip)
        {
            $offset = isset($offset_list[$key]) ? $offset_list[$key] : 0;
            $requestBufferArray["$ip:$port"] = json_encode(['cmd'        => 'get_log',
                                                            'module'     => $module,
                                                            'interface'  => $interface,
                                                            'start_time' => $start_time,
                                                            'offset'     => $offset,
                                                            'count'      => $count]) . "\n";
        }

        $read_buffer_array = StatServer::multiRequest($requestBufferArray);
        ksort($read_buffer_array);
        foreach ($read_buffer_array as $address => $buf)
        {
            list($ip, $port) = explode(':', $address);
            $body_data = json_decode(trim($buf), true);
            $log_data = isset($body_data['data']) ? $body_data['data'] : '';
            $offset = isset($body_data['offset']) ? $body_data['offset'] : 0;
            $read_buffer_array[$address] = ['offset' => $offset, 'data' => $log_data];
        }
        return $read_buffer_array;
    }

}