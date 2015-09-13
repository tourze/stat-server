<?php

namespace tourze\StatServer;

/**
 * Class Cache
 *
 * @package tourze\StatServer
 */
abstract class Cache
{

    /**
     * @var array 统计数据
     */
    public static $statisticData = [];

    /**
     * @var array 服务器IP列表
     */
    public static $serverIpList = [];

    /**
     * @var array 模块的数据
     */
    public static $moduleData = [];

    /**
     * @var array
     */
    public static $lastFailedIpArray = [];

    /**
     * @var array
     */
    public static $lastSuccessIpArray = [];
}
