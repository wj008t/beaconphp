<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 17:09
 */

namespace sdopx\compiler;


use sdopx\lib\Compiler;

class RdelimCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        return '$__out->html(' . var_export($compiler->source->right_delimiter, true) . ');';
    }
}