<?php

namespace stat\Protocol;

use tourze\Base\Helper\Arr;

/**
 *
 * struct statistic协议结构
 * {
 *     unsigned char module_name_len;
 *     unsigned char interface_name_len;
 *     float cost_time;
 *     unsigned char success;
 *     int code;
 *     unsigned short msg_len;
 *     unsigned int time;
 *     char[module_name_len] module_name;
 *     char[interface_name_len] interface_name;
 *     char[msg_len] msg;
 * }
 *
 * @author workerman.net
 */
class Statistic
{
    /**
     * @var int 包头长度
     */
    const PACKAGE_FIXED_LENGTH = 17;

    /**
     * @var int udp包最大长度
     */
    const MAX_UDP_PACKAGE_SIZE = 65507;

    /**
     * @var int char类型能保存的最大数值
     */
    const MAX_CHAR_VALUE = 255;

    /**
     * @var int unsigned short 能保存的最大数值
     */
    const MAX_UNSIGNED_SHORT_VALUE = 65535;

    /**
     * 处理输入数据
     *
     * @param string $receiveBuffer
     * @return int
     */
    public static function input($receiveBuffer)
    {
        if (strlen($receiveBuffer) < self::PACKAGE_FIXED_LENGTH)
        {
            return 0;
        }

        $data = self::unpack($receiveBuffer);
        return $data['module_name_len'] + $data['interface_name_len'] + $data['msg_len'] + self::PACKAGE_FIXED_LENGTH;
    }

    /**
     * 解包数据
     *
     * @param string $data
     * @return array
     */
    public static function unpack($data)
    {
        $result = unpack("Cmodule_name_len/Cinterface_name_len/fcost_time/Csuccess/Ncode/nmsg_len/Ntime", $data);
        return $result;
    }

    /**
     * 编码
     *
     * @param $data
     * @return string
     * @internal param string $module
     * @internal param string $interface
     * @internal param float $cost_time
     * @internal param int $success
     * @internal param int $code
     * @internal param string $msg
     */
    public static function encode($data)
    {
        $module = $data['module'];
        $interface = $data['interface'];
        $cost_time = $data['$cost_time'];
        $success = $data['success'];
        $code = isset($data['code']) ? $data['code'] : 0;
        $msg = isset($data['msg']) ? $data['msg'] : '';

        // 防止模块名过长
        if (strlen($module) > self::MAX_CHAR_VALUE)
        {
            $module = substr($module, 0, self::MAX_CHAR_VALUE);
        }

        // 防止接口名过长
        if (strlen($interface) > self::MAX_CHAR_VALUE)
        {
            $interface = substr($interface, 0, self::MAX_CHAR_VALUE);
        }

        // 防止msg过长
        $moduleNameLength = strlen($module);
        $interfaceNameLength = strlen($interface);
        $availableSize = self::MAX_UDP_PACKAGE_SIZE - self::PACKAGE_FIXED_LENGTH - $moduleNameLength - $interfaceNameLength;
        if (strlen($msg) > $availableSize)
        {
            $msg = substr($msg, 0, $availableSize);
        }

        // 打包
        return pack('CCfCNnN', $moduleNameLength, $interfaceNameLength, $cost_time, $success ? 1 : 0, $code, strlen($msg), time()) . $module . $interface . $msg;
    }

    /**
     * 解包
     *
     * @param string $receiveBuffer
     * @return array
     */
    public static function decode($receiveBuffer)
    {
        // 解包
        $data = self::unpack($receiveBuffer);
        $module = substr($receiveBuffer, self::PACKAGE_FIXED_LENGTH, Arr::get($data, 'module_name_len'));
        $interface = substr($receiveBuffer, self::PACKAGE_FIXED_LENGTH + $data['module_name_len'], $data['interface_name_len']);
        $msg = substr($receiveBuffer, self::PACKAGE_FIXED_LENGTH + $data['module_name_len'] + $data['interface_name_len']);
        return [
            'module'    => $module,
            'interface' => $interface,
            'cost_time' => $data['cost_time'],
            'success'   => $data['success'],
            'time'      => $data['time'],
            'code'      => $data['code'],
            'msg'       => $msg,
        ];
    }
}
