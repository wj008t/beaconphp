<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 23:23
 */

namespace sdopx\plugin;


class RmhtmlModifier
{
    public static function execute($string)
    {
        return trim(preg_replace('@<.*>@si', '', $string));
    }

    public static function upper($str)
    {
        return strtoupper($str);
    }
}