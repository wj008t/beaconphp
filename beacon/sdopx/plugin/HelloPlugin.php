<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 21:40
 */

namespace sdopx\plugin;


use sdopx\lib\Outer;


class HelloPlugin
{
    public static function block($param, $func, Outer $out)
    {
        $out->html('data');
        for ($i = 0; $i < 10; $i++) {
            $func($i);
        }
        $out->html('end');
    }
}