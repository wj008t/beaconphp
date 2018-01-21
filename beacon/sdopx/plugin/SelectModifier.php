<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 23:42
 */

namespace sdopx\plugin;


class SelectModifier
{
    public static function execute($string, $map, $def = '')
    {
        if (empty($string)) {
            return $def;
        }
        if (isset($map[$string])) {
            return $map[$string];
        }
        return $def;
    }
}