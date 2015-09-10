<?php

use tourze\Base\Helper\Arr;
use tourze\Html\Tag\A;

/** @var string $interfaceName */
/** @var string $query */
/** @var string $errorMsg */
/** @var string $noticeMsg */
/** @var array $data */
/** @var string $successSeriesData */
/** @var string $failSeriesData */
/** @var string $successTimeSeriesData */
/** @var string $failTimeSeriesData */
/** @var string $codePieData */
/** @var int $globalRate */

?>
<div class="container">
    <div class="row clearfix">
        <div class="col-md-12 column">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="/">概述</a>
                </li>
                <li>
                    <a href="/?fn=statistic">监控</a>
                </li>
                <li>
                    <a href="/?fn=logger">日志</a>
                </li>
                <li class="disabled">
                    <a href="#">告警</a>
                </li>
                <li class="dropdown pull-right">
                    <a href="#" data-toggle="dropdown" class="dropdown-toggle">其它<strong class="caret"></strong></a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="/?fn=admin&act=detect_server">探测数据源</a>
                        </li>
                        <li>
                            <a href="/?fn=admin">数据源管理</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="row clearfix">
        <div class="col-md-12 column">
            <?php
            if ($errorMsg)
            { ?>
                <div class="alert alert-dismissable alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><?php echo $errorMsg; ?></strong>
                </div>
            <?php }
            elseif ($noticeMsg)
            { ?>
                <div class="alert alert-dismissable alert-info">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><?php echo $noticeMsg; ?></strong>
                </div>
            <?php } ?>
            <div class="row clearfix">
                <div class="col-md-12 column text-center">
                    <?php
                    for ($i = 13; $i >= 1; $i--)
                    {
                        $theTime = strtotime("-$i day");
                        $theDate = date('Y-m-d', $theTime);
                        $htmlTheDate = $date == $theDate ? "<b>$theDate</b>" : $theDate;
                        echo new A([
                            'href'  => '/?date=' . "$theDate&$query",
                            'class' => 'btn ' . $htmlClass,
                            'type'  => 'button',
                        ], $htmlTheDate);
                        if ($i == 7)
                        {
                            echo '</br>';
                        }
                    }

                    $theDate = date('Y-m-d');
                    $htmlTheDate = $date == $theDate ? "<b>$theDate</b>" : $theDate;
                    echo '<a href="/?date=' . "$theDate&$query" . '" class="btn" type="button">' . $htmlTheDate . '</a>';
                    ?>
                </div>
            </div>
            <div class="row clearfix">
                <div class="col-md-6 column height-400" id="suc-pie">
                </div>
                <div class="col-md-6 column height-400" id="code-pie">
                </div>
            </div>
            <div class="row clearfix">
                <div class="col-md-12 column height-400" id="req-container">
                </div>
            </div>
            <div class="row clearfix">
                <div class="col-md-12 column height-400" id="time-container">
                </div>
            </div>
            <script>
                Highcharts.setOptions({
                    global: {
                        useUTC: false
                    }
                });
                $('#suc-pie').highcharts({
                    chart: {
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false
                    },
                    title: {
                        text: '<?php echo $date;?> 可用性'
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                color: '#000000',
                                connectorColor: '#000000',
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    credits: {
                        enabled: false,
                    },
                    series: [{
                        type: 'pie',
                        name: '可用性',
                        data: [
                            {
                                name: '可用',
                                y: <?php echo $globalRate;?>,
                                sliced: true,
                                selected: true,
                                color: '#2f7ed8'
                            },
                            {
                                name: '不可用',
                                y: <?php echo (100-$globalRate);?>,
                                sliced: true,
                                selected: true,
                                color: '#910000'
                            }
                        ]
                    }]
                });
                $('#code-pie').highcharts({
                    chart: {
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false
                    },
                    title: {
                        text: '<?php echo $date;?> 返回码分布'
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                color: '#000000',
                                connectorColor: '#000000',
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    credits: {
                        enabled: false,
                    },
                    series: [{
                        type: 'pie',
                        name: '返回码分布',
                        data: [
                            <?php echo $codePieData;?>
                        ]
                    }]
                });
                $('#req-container').highcharts({
                    chart: {
                        type: 'spline'
                    },
                    title: {
                        text: '<?php echo "$date $interfaceName";?>  请求量曲线'
                    },
                    subtitle: {
                        text: ''
                    },
                    xAxis: {
                        type: 'datetime',
                        dateTimeLabelFormats: {
                            hour: '%H:%M'
                        }
                    },
                    yAxis: {
                        title: {
                            text: '请求量(次/5分钟)'
                        },
                        min: 0
                    },
                    tooltip: {
                        formatter: function () {
                            return '<p style="color:' + this.series.color + ';font-weight:bold;">'
                                + this.series.name +
                                '</p><br /><p style="color:' + this.series.color + ';font-weight:bold;">时间：' + Highcharts.dateFormat('%m月%d日 %H:%M', this.x) +
                                '</p><br /><p style="color:' + this.series.color + ';font-weight:bold;">数量：' + this.y + '</p>';
                        }
                    },
                    credits: {
                        enabled: false,
                    },
                    series: [{
                        name: '成功曲线',
                        data: [
                            <?php echo $successSeriesData;?>
                        ],
                        lineWidth: 2,
                        marker: {
                            radius: 1
                        },

                        pointInterval: 300 * 1000
                    },
                        {
                            name: '失败曲线',
                            data: [
                                <?php echo $failSeriesData;?>
                            ],
                            lineWidth: 2,
                            marker: {
                                radius: 1
                            },
                            pointInterval: 300 * 1000,
                            color: '#9C0D0D'
                        }]
                });
                $('#time-container').highcharts({
                    chart: {
                        type: 'spline'
                    },
                    title: {
                        text: '<?php echo "$date $interfaceName";?>  请求耗时曲线'
                    },
                    subtitle: {
                        text: ''
                    },
                    xAxis: {
                        type: 'datetime',
                        dateTimeLabelFormats: {
                            hour: '%H:%M'
                        }
                    },
                    yAxis: {
                        title: {
                            text: '平均耗时(单位：秒)'
                        },
                        min: 0
                    },
                    tooltip: {
                        formatter: function () {
                            return '<p style="color:' + this.series.color + ';font-weight:bold;">'
                                + this.series.name +
                                '</p><br /><p style="color:' + this.series.color + ';font-weight:bold;">时间：' + Highcharts.dateFormat('%m月%d日 %H:%M', this.x) +
                                '</p><br /><p style="color:' + this.series.color + ';font-weight:bold;">平均耗时：' + this.y + '</p>';
                        }
                    },
                    credits: {
                        enabled: false,
                    },
                    series: [{
                        name: '成功曲线',
                        data: [
                            <?php echo $successTimeSeriesData;?>
                        ],
                        lineWidth: 2,
                        marker: {
                            radius: 1
                        },
                        pointInterval: 300 * 1000
                    },
                        {
                            name: '失败曲线',
                            data: [
                                <?php echo $failTimeSeriesData;?>
                            ],
                            lineWidth: 2,
                            marker: {
                                radius: 1
                            },
                            pointInterval: 300 * 1000,
                            color: '#9C0D0D'
                        }]
                });
            </script>
            <table class="table table-hover table-condensed table-bordered">
                <thead>
                <tr>
                    <th>时间</th>
                    <th>调用总数</th>
                    <th>平均耗时</th>
                    <th>成功调用总数</th>
                    <th>成功平均耗时</th>
                    <th>失败调用总数</th>
                    <th>失败平均耗时</th>
                    <th>成功率</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $htmlClass = '';
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
                    elseif ($item['precent'] >= 99.99)
                    {
                        $htmlClass = 'class="success"';
                    }
                    elseif ($item['precent'] >= 99)
                    {
                        $htmlClass = '';
                    }
                    elseif ($item['precent'] >= 98)
                    {
                        $htmlClass = 'class="warning"';
                    }
                    ?>
                    <tr <?php echo $htmlClass ?>>
                        <td><?php echo Arr::get($item, 'time') ?></td>
                        <td><?php echo Arr::get($item, 'total_count') ?></td>
                        <td><?php echo Arr::get($item, 'total_avg_time') ?></td>
                        <td><?php echo Arr::get($item, 'success_count') ?></td>
                        <td><?php echo Arr::get($item, 'suc_avg_time') ?></td>
                        <td><?php
                            echo Arr::get($item, 'fail_count') > 0
                                ? new A([
                                    'href' => '/logger?' . $query . '&' . http_build_query([
                                            'start_time' => strtotime(Arr::get($item, 'time')) - 300,
                                            'end_time'   => strtotime(Arr::get($item, 'time')),
                                        ]),
                                ], Arr::get($item, 'fail_count'))
                                : Arr::get($item, 'fail_count')
                            ?></td>
                        <td><?php echo Arr::get($item, 'fail_avg_time') ?></td>
                        <td><?php echo Arr::get($item, 'precent') ?>%</td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
