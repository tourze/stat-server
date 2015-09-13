<?php

namespace tourze\StatServer\Controller;

use tourze\Base\Config;
use tourze\StatServer\Cache;
use tourze\StatServer\StatServer;
use tourze\View\View;

/**
 * 首页控制器
 *
 * @package tourze\StatServer\Controller
 */
class MainController extends BaseController
{

    /**
     * 入口
     */
    public function actionIndex()
    {
        $date = $this->request->query('date');
        if ( ! $date)
        {
            $date = date('Y-m-d');
        }

        $errorMsg = $noticeMsg = '';
        $module = 'WorkerMan';
        $interface = 'Statistics';
        $today = date('Y-m-d');
        $timeNow = time();
        StatServer::multiRequestStAndModules($module, $interface, $date);
        $allStr = '';
        if (is_array(Cache::$statisticData['statistic']))
        {
            foreach (Cache::$statisticData['statistic'] as $ip => $st_str)
            {
                $allStr .= $st_str;
            }
        }

        $codeMap = [];
        $data = StatServer::formatStatLog($allStr, $date, $codeMap);
        $successSeriesData = $failSeriesData = $successTimeSeriesData = $failTimeSeriesData = [];
        $totalCount = $fail_count = 0;
        foreach ($data as $time_point => $item)
        {
            if ($item['total_count'])
            {
                $successSeriesData[] = "[" . ($time_point * 1000) . ",{$item['total_count']}]";
                $totalCount += $item['total_count'];
            }
            $failSeriesData[] = "[" . ($time_point * 1000) . ",{$item['fail_count']}]";
            $fail_count += $item['fail_count'];
            if ($item['total_avg_time'])
            {
                $successTimeSeriesData[] = "[" . ($time_point * 1000) . ",{$item['total_avg_time']}]";
            }
            $failTimeSeriesData[] = "[" . ($time_point * 1000) . ",{$item['fail_avg_time']}]";
        }
        $successSeriesData = implode(',', $successSeriesData);
        $failSeriesData = implode(',', $failSeriesData);
        $successTimeSeriesData = implode(',', $successTimeSeriesData);
        $failTimeSeriesData = implode(',', $failTimeSeriesData);

        // 总体成功率
        $globalRate = $totalCount ? round((($totalCount - $fail_count) / $totalCount) * 100, 4) : 100;
        // 返回码分布
        $codePieData = '';
        $codePieArray = [];
        unset($codeMap[0]);
        if (empty($codeMap))
        {
            $codeMap[0] = $totalCount > 0 ? $totalCount : 1;
        }
        if (is_array($codeMap))
        {
            $total_item_count = array_sum($codeMap);
            foreach ($codeMap as $code => $count)
            {
                $codePieArray[] = "[\"$code:{$count}个\", " . round($count * 100 / $total_item_count, 4) . "]";
            }
            $codePieData = implode(',', $codePieArray);
        }

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

        if (Cache::$lastFailedIpArray)
        {
            $errorMsg = '<strong>无法从以下数据源获取数据:</strong>';
            foreach (Cache::$lastFailedIpArray as $ip)
            {
                $errorMsg .= $ip . ':' . Config::load('statServer')->get('providerPort') . '&nbsp;';
            }
        }

        $this->template->set('content', View::factory('main/index', [
            'date'                  => $date,
            'query'                 => $query,
            'interfaceName'         => '整体',
            'errorMsg'              => $errorMsg,
            'noticeMsg'             => $noticeMsg,
            'data'                  => $data,
            'successSeriesData'     => $successSeriesData,
            'failSeriesData'        => $failSeriesData,
            'successTimeSeriesData' => $successTimeSeriesData,
            'failTimeSeriesData'    => $failTimeSeriesData,
            'globalRate'            => $globalRate,
            'codePieData'           => $codePieData,
        ]));
    }

}
