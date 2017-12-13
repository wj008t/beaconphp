<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace sdopx\compile {

    /**
     * Description of Compile_Foreach
     *
     * @author wj008
     */
    class Compile_Foreach extends CompileBase {

        public function compile($args, $compiler) {
            $from = isset($args['from']) ? $args['from'] : '';
            $item = isset($args['item']) ? trim($args['item'], '\'"') : '';
            $key = isset($args['key']) ? trim($args['key'], '\'"') : '';
            $prop = isset($args['prop']) ? trim($args['prop'], '\'"') : '';
            if (preg_match('@^\w+$@', $item) == 0) {
                $compiler->trigger_template_error('foreach 标签中的 item 属性 必须是 变量字符串格式。');
                return '';
            }
            if (!empty($key) && preg_match('@^\w+$@', $key) == 0) {
                $compiler->trigger_template_error('foreach 标签中的 key 属性 必须是 变量字符串格式。');
                return '';
            }
            if (!empty($prop) && preg_match('@^\w+$@', $prop) == 0) {
                $compiler->trigger_template_error('foreach 标签中的 prop 属性 必须是 变量字符串格式。');
                return '';
            }
            //获得一个变量前缀--
            $prefix = $this->getTempPrefix('$temp');
            $vars = [];
            $vars[$item] = $prefix . '_@key';
            $vars['index'] = $prefix . '_@key';
            if (!empty($key)) {
                $vars[$key] = $prefix . '_@key';
            }
            if (!empty($prop)) {
                $vars[$prop] = $prefix . '_@key';
            }
            $this->addTKey($compiler, $vars);
            if (empty($from) && empty($item) && !empty($args['code'])) {
                $_output = "<?php {$prefix}_index=-1;\n";
                $_output .= "foreach({$args['code']}){ {$prefix}_index++;?>";
                $this->openTag($compiler, 'foreach', ['foreach', $vars, $prefix, false, $prop]);
            } else {
                if (empty($from)) {
                    $compiler->trigger_template_error('foreach 标签中缺少 from');
                }
                if (empty($item)) {
                    $compiler->trigger_template_error('foreach 标签中缺少 item');
                }
                if (!empty($prop)) {
                    $_output = "<?php 
                    \$temp_form={$from};
                    {$prefix}_{$prop}=[];
                    {$prefix}_{$prop}['index']=-1;
                    {$prefix}_{$prop}['iteration']=-1;
                    {$prefix}_{$prop}['total']=count(\$temp_form);
                    {$prefix}_{$prop}['first']=true;
                    {$prefix}_{$prop}['last']=false;";
                    if (!empty($key)) {
                        $_output .= "foreach(\$temp_form as {$prefix}_{$key} => {$prefix}_{$item}){";
                    } else {
                        $_output .= "foreach(\$temp_form as {$prefix}_{$item}){";
                    }
                    $_output .= "{$prefix}_{$prop}['index']++;
{$prefix}_{$prop}['iteration']={$prefix}_{$prop}['index']+1;
{$prefix}_{$prop}['first']={$prefix}_{$prop}['index']==0;
{$prefix}_{$prop}['last']={$prefix}_{$prop}['index']=={$prefix}_{$prop}['total']-1;
    ?>";
                    $this->openTag($compiler, 'foreach', ['foreach', $vars, $prefix, true, $prop]);
                } else {
                    $_output = "<?php {$prefix}_index=-1;\n";
                    if (!empty($key)) {
                        $_output .= "foreach({$from} as {$prefix}_{$key} => {$prefix}_{$item}){ {$prefix}_index++;?>";
                    } else {
                        $_output .= "foreach({$from} as {$prefix}_{$item}){ {$prefix}_index++;?>";
                    }
                    $this->openTag($compiler, 'foreach', ['foreach', $vars, $prefix, false, $prop]);
                }
            }
            return $_output;
        }

    }

    class Compile_Foreachelse extends CompileBase {

        public function compile($args, $compiler) {
            list($openTag, $vars, $prefix, $useprop, $prop) = $this->closeTag($compiler, ['foreach']);
            $this->openTag($compiler, 'foreachelse', ['foreachelse', $vars, $prefix, $useprop, $prop]);
            if ($useprop) {
                return "<?php }\nif ({$prefix}_{$prop}['index']<0) {?>";
            }
            return "<?php }\nif ({$prefix}_index<0) {?>";
        }

    }

    class Compile_Foreachclose extends CompileBase {

        public function compile($args, $compiler) {
            list($openTag, $vars, $prefix, $useprop, $prop) = $this->closeTag($compiler, ['foreach', 'foreachelse']);
            $this->removeTKey($compiler, $vars);
            return "<?php } ?>";
        }

    }

}