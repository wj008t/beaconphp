<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace sdopx\compile;

/**
 * Description of Compile_Include
 *
 * @author wj008
 */
class Compile_Include {

    public function compile($args, $compiler) {
        if (!isset($args['file'])) {
            $compiler->trigger_template_error('include 标签中的 file 属性是必选项。');
        }
        $file = $args['file'];
        unset($args['file']);
        $echo = '';
        $output = empty($args['output']) ? 'true' : $args['output'];
        $_sdopx = $compiler->sdopx;
        eval("\$output = $output;");
        if ($output) {
            $echo = 'echo ';
        }
        unset($args['output']);
        if (count($args) == 0) {
            return "<?php {$echo} \$_sdopx->getSubTemplate({$file});?>";
        }
        $temp = [];
        foreach ($args as $_key => $_value) {
            $temp[] = "'$_key'=>$_value";
        }
        $_params = '[' . implode(",", $temp) . ']';
        $_output = "<?php {$echo} \$_sdopx->getSubTemplate({$file},{$_params});?>";
        return $_output;
    }

}
