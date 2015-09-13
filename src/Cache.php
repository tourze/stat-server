<?php

namespace tourze\StatServer;

class Cache
{
    public static $statDataCache = [];
    public static $serverIpList       = [];
    public static $modulesDataCache   = [];
    public static $lastFailedIpArray  = [];
    public static $lastSuccessIpArray = [];
}
