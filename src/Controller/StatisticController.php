<?php

namespace tourze\StatServer\Controller;

use tourze\Base\Config;
use tourze\Route\Route;
use tourze\StatServer\Cache;
use tourze\StatServer\StatServer;
use tourze\View\View;

/**
 * 监控控制器
 *
 * @package tourze\StatServer\Controller
 */
class StatisticController extends BaseController
{

    /**
     * 默认动作
     */
    public function actionIndex()
    {
        $module = $this->request->query('module');
        $interface = $this->request->query('interface');
        $date = $this->request->query('date');
        if ( ! $date)
        {
            $date = date('Y-m-d');
        }

        $errorMsg = '';
        $today = date('Y-m-d');
        $timeNow = time();
        StatServer::multiRequestStAndModules($module, $interface, $date);
        $allStr = '';
        if (is_array(Cache::$statDataCache['statistic']))
        {
            foreach (Cache::$statDataCache['statistic'] as $ip => $st_str)
            {
                $allStr .= $st_str;
            }
        }

        $codeMap = [];
        $data = StatServer::formatStatLog($allStr, $date, $codeMap);
        $interfaceName = "$module::$interface";
        $successSeriesData = $failSeriesData = $successTimeSeriesData = $failTimeSeriesData = [];
        $totalCount = $failCount = 0;
        foreach ($data as $timePoint => $item)
        {
            if ($item['total_count'])
            {
                $successSeriesData[] = "[" . ($timePoint * 1000) . ",{$item['total_count']}]";
                $totalCount += $item['total_count'];
            }
            $failSeriesData[] = "[" . ($timePoint * 1000) . ",{$item['fail_count']}]";
            $failCount += $item['fail_count'];
            if ($item['total_avg_time'])
            {
                $successTimeSeriesData[] = "[" . ($timePoint * 1000) . ",{$item['total_avg_time']}]";
            }
            $failTimeSeriesData[] = "[" . ($timePoint * 1000) . ",{$item['fail_avg_time']}]";
        }
        $successSeriesData = implode(',', $successSeriesData);
        $failSeriesData = implode(',', $failSeriesData);
        $successTimeSeriesData = implode(',', $successTimeSeriesData);
        $failTimeSeriesData = implode(',', $failTimeSeriesData);

        unset($_GET['start_time'], $_GET['end_time'], $_GET['date'], $_GET['fn']);
        $query = http_build_query($_GET);

        // 删除末尾0的记录
        if ($today == $date)
        {
            while ( ! empty($data) && ($item = end($data)) && $item['total_count'] == 0 && ($key = key($data)) && $timeNow < $key)
            {
                unset($data[$key]);
            }
        }

        $tableData = $htmlClass = '';
        if ($data)
        {
            $firstLine = true;
            foreach ($data as $item)
            {
                if ($firstLine)
                {
                    $firstLine = false;
                    if ($item['total_count'] == 0)
                    {
                        continue;
                    }
                }
                $htmlClass = 'class="danger"';
                if ($item['total_count'] == 0)
                {
                    $htmlClass = '';
                }
                elseif ($item['percent'] >= 99.99)
                {
                    $htmlClass = 'class="success"';
                }
                elseif ($item['percent'] >= 99)
                {
                    $htmlClass = '';
                }
                elseif ($item['percent'] >= 98)
                {
                    $htmlClass = 'class="warning"';
                }
                $tableData .= "\n<tr $htmlClass>
            <td>{$item['time']}</td>
            <td>{$item['total_count']}</td>
            <td> {$item['total_avg_time']}</td>
            <td>{$item['success_count']}</td>
            <td>{$item['suc_avg_time']}</td>
            <td>" . ($item['fail_count'] > 0 ? ("<a href='" . Route::url('stat-web', ['controller' => 'Logger']) . "?$query&start_time=" . (strtotime($item['time']) - 300) . "&end_time=" . (strtotime($item['time'])) . "'>{$item['fail_count']}</a>") : $item['fail_count']) . "</td>
            <td>{$item['fail_avg_time']}</td>
            <td>{$item['percent']}%</td>
            </tr>
            ";
            }
        }

        // date btn
        $dateBtnStr = '';
        for ($i = 13; $i >= 1; $i--)
        {
            $theTime = strtotime("-$i day");
            $theDate = date('Y-m-d', $theTime);
            $htmlTheDate = $date == $theDate ? "<b>$theDate</b>" : $theDate;
            $dateBtnStr .= '<a href="' . Route::url('stat-web', ['controller' => 'Statistic']) . '?date=' . "$theDate&$query" . '" class="btn ' . $htmlClass . '" type="button">' . $htmlTheDate . '</a>';
            if ($i == 7)
            {
                $dateBtnStr .= '</br>';
            }
        }
        $theDate = date('Y-m-d');
        $htmlTheDate = $date == $theDate ? "<b>$theDate</b>" : $theDate;
        $dateBtnStr .= '<a href="' . Route::url('stat-web', ['controller' => 'Statistic']) . '?date=' . "$theDate&$query" . '" class="btn" type="button">' . $htmlTheDate . '</a>';

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

        if (Cache::$lastFailedIpArray)
        {
            $errorMsg = '<strong>无法从以下数据源获取数据:</strong>';
            foreach (Cache::$lastFailedIpArray as $ip)
            {
                $errorMsg .= $ip . '::' . Config::load('statServer')->get('providerPort') . '&nbsp;';
            }
        }

        $this->template->set('content', View::factory('statistic/index', [
            'errorMsg'              => $errorMsg,
            'date'                  => $date,
            'module'                => $module,
            'interface'             => $interface,
            'interfaceName'         => $interfaceName,
            'moduleStr'             => $moduleStr,
            'successSeriesData'     => $successSeriesData,
            'failSeriesData'        => $failSeriesData,
            'successTimeSeriesData' => $successTimeSeriesData,
            'failTimeSeriesData'    => $failTimeSeriesData,
            'tableData'             => $tableData,
            'dateBtnStr'            => $dateBtnStr,
        ]));
    }

}
