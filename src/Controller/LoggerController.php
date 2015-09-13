<?php

namespace tourze\StatServer\Controller;

use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Route\Route;
use tourze\StatServer\Cache;
use tourze\StatServer\StatServer;
use tourze\View\View;

/**
 * 日志查看控制器
 *
 * @package tourze\StatServer\Controller
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
        $startTime = $this->request->query('start_time');
        $endTime = $this->request->query('end_time');
        $offset = $this->request->query('offset');
        $count = $this->request->query('count');

        $moduleStr = '';
        foreach (Cache::$modulesDataCache as $mod => $interfaces)
        {
            if ($mod == 'WorkerMan')
            {
                continue;
            }
            $moduleStr .= '<li><a href="' . Route::url('stat-web', ['controller' => 'Statistic']) . '?module=' . $mod . '">' . $mod . '</a></li>';
            if ($module == $mod)
            {
                foreach ($interfaces as $if)
                {
                    $moduleStr .= '<li>&nbsp;&nbsp;<a href="' . Route::url('stat-web', ['controller' => 'Statistic']) . '?module=' . $mod . '&interface=' . $if . '">' . $if . '</a></li>';
                }
            }
        }

        $logDataArray = $this->getStasticLog($module, $interface, $startTime, $endTime, $offset, $count);
        unset($_GET['ip'], $_GET['offset']);
        $logStr = '';
        foreach ($logDataArray as $address => $log_data)
        {
            $temp = explode(':', $address);
            $ip = array_shift($temp);
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

    public function getStasticLog($module, $interface, $startTime, $endTime, $offset = '', $count = 10)
    {
        $ipList = ( ! empty($_GET['ip']) && is_array($_GET['ip'])) ? $_GET['ip'] : Cache::$serverIpList;
        $offsetList = ( ! empty($_GET['offset']) && is_array($_GET['offset'])) ? $_GET['offset'] : [];
        $port = Config::load('statServer')->get('providerPort');
        $requestBufferArray = [];
        foreach ($ipList as $key => $ip)
        {
            $offset = isset($offsetList[$key]) ? $offsetList[$key] : 0;
            $buffer = [
                'cmd'        => 'get-log',
                'module'     => $module,
                'interface'  => $interface,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'offset'     => $offset,
                'count'      => $count,
            ];
            Base::getLog()->info(__METHOD__ . ' generate buffer fot getting log', $buffer);
            $requestBufferArray["$ip:$port"] = json_encode($buffer) . "\n";
        }

        $readBufferArray = StatServer::multiRequest($requestBufferArray);
        ksort($readBufferArray);
        foreach ($readBufferArray as $address => $buf)
        {
            $bodyData = json_decode(trim($buf), true);
            $logData = isset($bodyData['data']) ? $bodyData['data'] : '';
            $offset = isset($bodyData['offset']) ? $bodyData['offset'] : 0;
            $readBufferArray[$address] = ['offset' => $offset, 'data' => $logData];
        }
        return $readBufferArray;
    }

}
