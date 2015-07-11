<?php

namespace stat;

class Base
{

    public static function multiRequestStAndModules($module, $interface, $date)
    {
        \Statistics\Lib\Cache::$statisticDataCache['statistic'] = '';
        $buffer = json_encode(['cmd' => 'get_statistic', 'module' => $module, 'interface' => $interface, 'date' => $date]) . "\n";
        $ip_list = ( ! empty($_GET['ip']) && is_array($_GET['ip'])) ? $_GET['ip'] : \Statistics\Lib\Cache::$ServerIpList;
        $reqest_buffer_array = [];
        $port = \Statistics\Config::$ProviderPort;;
        foreach ($ip_list as $ip)
        {
            $reqest_buffer_array["$ip:$port"] = $buffer;
        }
        $read_buffer_array = multiRequest($reqest_buffer_array);
        foreach ($read_buffer_array as $address => $buf)
        {
            list($ip, $port) = explode(':', $address);
            $body_data = json_decode(trim($buf), true);
            $statistic_data = isset($body_data['statistic']) ? $body_data['statistic'] : '';
            $modules_data = isset($body_data['modules']) ? $body_data['modules'] : [];
            // 整理modules
            foreach ($modules_data as $mod => $interfaces)
            {
                if ( ! isset(\Statistics\Lib\Cache::$modulesDataCache[$mod]))
                {
                    \Statistics\Lib\Cache::$modulesDataCache[$mod] = [];
                }
                foreach ($interfaces as $if)
                {
                    \Statistics\Lib\Cache::$modulesDataCache[$mod][$if] = $if;
                }
            }
            \Statistics\Lib\Cache::$statisticDataCache['statistic'][$ip] = $statistic_data;
        }
    }

    public static function formatSt($str, $date, &$code_map)
    {
        // time:[suc_count:xx,suc_cost_time:xx,fail_count:xx,fail_cost_time:xx]
        $st_data = $code_map = [];
        $st_explode = explode("\n", $str);
        // 汇总计算
        foreach ($st_explode as $line)
        {
            // line = IP time suc_count suc_cost_time fail_count fail_cost_time code_json
            $line_data = explode("\t", $line);
            if ( ! isset($line_data[5]))
            {
                continue;
            }
            $time_line = $line_data[1];
            $time_line = ceil($time_line / 300) * 300;
            $suc_count = $line_data[2];
            $suc_cost_time = $line_data[3];
            $fail_count = $line_data[4];
            $fail_cost_time = $line_data[5];
            $tmp_code_map = json_decode($line_data[6], true);
            if ( ! isset($st_data[$time_line]))
            {
                $st_data[$time_line] = ['suc_count' => 0, 'suc_cost_time' => 0, 'fail_count' => 0, 'fail_cost_time' => 0];
            }
            $st_data[$time_line]['suc_count'] += $suc_count;
            $st_data[$time_line]['suc_cost_time'] += $suc_cost_time;
            $st_data[$time_line]['fail_count'] += $fail_count;
            $st_data[$time_line]['fail_cost_time'] += $fail_cost_time;

            if (is_array($tmp_code_map))
            {
                foreach ($tmp_code_map as $code => $count)
                {
                    if ( ! isset($code_map[$code]))
                    {
                        $code_map[$code] = 0;
                    }
                    $code_map[$code] += $count;
                }
            }
        }
        // 按照时间排序
        ksort($st_data);
        // time => [total_count:xx,suc_count:xx,suc_avg_time:xx,fail_count:xx,fail_avg_time:xx,percent:xx]
        $data = [];
        // 计算成功率 耗时
        foreach ($st_data as $time_line => $item)
        {
            $data[$time_line] = [
                'time'           => date('Y-m-d H:i:s', $time_line),
                'total_count'    => $item['suc_count'] + $item['fail_count'],
                'total_avg_time' => $item['suc_count'] + $item['fail_count'] == 0 ? 0 : round(($item['suc_cost_time'] + $item['fail_cost_time']) / ($item['suc_count'] + $item['fail_count']), 6),
                'suc_count'      => $item['suc_count'],
                'suc_avg_time'   => $item['suc_count'] == 0 ? $item['suc_count'] : round($item['suc_cost_time'] / $item['suc_count'], 6),
                'fail_count'     => $item['fail_count'],
                'fail_avg_time'  => $item['fail_count'] == 0 ? 0 : round($item['fail_cost_time'] / $item['fail_count'], 6),
                'precent'        => $item['suc_count'] + $item['fail_count'] == 0 ? 0 : round(($item['suc_count'] * 100 / ($item['suc_count'] + $item['fail_count'])), 4),
            ];
        }
        $time_point = strtotime($date);
        for ($i = 0; $i < 288; $i++)
        {
            $data[$time_point] = isset($data[$time_point]) ? $data[$time_point] :
                [
                    'time'           => date('Y-m-d H:i:s', $time_point),
                    'total_count'    => 0,
                    'total_avg_time' => 0,
                    'suc_count'      => 0,
                    'suc_avg_time'   => 0,
                    'fail_count'     => 0,
                    'fail_avg_time'  => 0,
                    'precent'        => 100,
                ];
            $time_point += 300;
        }
        ksort($data);
        return $data;
    }
}
