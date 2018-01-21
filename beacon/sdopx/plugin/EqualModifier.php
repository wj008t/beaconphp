<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 23:42
 */

namespace sdopx\plugin;


class EqualModifier
{
    public static function execute($string, $compare, $val1, $val2 = '')
    {
        return $string == $compare ? $val1 : $val2;
    }
}