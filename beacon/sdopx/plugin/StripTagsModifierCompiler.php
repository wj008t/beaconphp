<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 23:53
 */

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class StripTagsModifierCompiler
{
    public static function compile(Compiler $compiler, array $args)
    {
        if (!isset($params[1]) || $args[1] === true || trim($args[1], '"') == 'true') {
            return "preg_replace('!<[^>]*?>!', ' ', {$args[0]})";
        } else {
            return 'strip_tags(' . $args[0] . ')';
        }
    }
}