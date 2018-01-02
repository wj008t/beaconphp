<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 23:53
 */

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class LowerModifierCompiler
{
    public static function compile(Compiler $compiler, array $args)
    {
        return 'strtolower(' . $args[0] . ')';
    }
}