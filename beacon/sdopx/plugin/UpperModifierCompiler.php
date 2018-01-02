<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 23:53
 */

namespace sdopx\plugin;


class UpperModifierCompiler
{
    public static function compile(Compiler $compiler, array $args)
    {
        return 'strtoupper(' . $args[0] . ')';
    }
}