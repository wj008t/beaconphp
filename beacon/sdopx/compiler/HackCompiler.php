<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/16
 * Time: 0:52
 */

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class HackCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $fn = isset($args['fn']) ? $args['fn'] : null;
        if (empty($fn)) {
            $compiler->addError("{function} 标签中 fn 函数名属性不能为空");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^\w+$@', $fn)) {
            $compiler->addError("{function} 标签中 fn 函数名只能是 字母数字");
        }
        $codes = [];
        $temp = [];
        $output = [];
        $params = $compiler->getTempPrefix('params');
        $varMap = $compiler->getVariableMap($params);
        foreach ($args as $key => $value) {
            if ($key === 'fn') {
                continue;
            }
            $value = empty($value) ? 'null' : $value;
            $varMap->add($key);
            $temp[] = "\${$params}_{$key}";
            $codes[] = "\${$params}_{$key}=isset(\${$params}['{$key}'])?\${$params}['{$key}']:{$value};";
        }
        $compiler->addVariableMap($varMap);
        $compiler->openTag('function', [$params, $fn]);
        $output[] = "\$_sdopx->hackMap['{$fn}']=function(\${$params}) use (\$_sdopx){";
        $output[] = '$__out=new \sdopx\lib\Outer($_sdopx);';
        $output[] = join("\n", $codes);
        $code = join("\n", $output);
        return $code;
    }
}

class HackCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($name, $data) = $compiler->closeTag(['function']);
        $compiler->removeVar($data[0]);

        return 'return $__out->getCode();};';
    }
}