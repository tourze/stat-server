<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="utf-8">
    <title>Stat监控</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="<?php echo \tourze\Tourze\Asset::minUrl(['bootstrap/dist/css/bootstrap.min.css']) ?>" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">

    <script type="text/javascript" src="<?php echo \tourze\Tourze\Asset::minUrl([
        'jquery/dist/jquery.min.js',
        'bootstrap/dist/js/bootstrap.min.js',
        'highcharts/highcharts.js',
    ]) ?>"></script>
</head>
<body>
<?php
/** @var mixed $content */
echo $content;
?>
<div class="footer">Powered by <a href="http://www.tourze.com" target="_blank"><strong>Tourze</strong></a></div>
</body>
</html>
