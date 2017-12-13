<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace sdopx\compile {

    /**
     * Description of Compile_Function
     *
     * @author wj008
     */
    class Compile_Function extends CompileBase {

        /**
         * @param array $args
         * @param sdopx\libs\Compile $compiler
         */
        public function compile($args, $compiler) {
            $name = isset($args['name']) ? trim($args['name'], '\'"') : '';
            if (0 == preg_match('@^\w+$@', $name)) {
                $compiler->trigger_template_error('function 函数标签中 name属性必须是 字符开头或者下划线开头的字符串。');
            }
            unset($args['name']);
            $attr = [];
            $fdefs = [];
            $prefix = $this->getTempPrefix('$params');
            foreach ($args as $key => $value) {
                $attr[$key] = $prefix . '[\'@key\']';
                $fdefs[] = " {$prefix}['" . $key . "']=array_key_exists('" . $key . "',{$prefix})?{$prefix}['" . $key . "']:{$value};";
            }
            $this->addTKey($compiler, $attr);
            $this->openTag($compiler, 'function', ['function', $attr]);
            $fdefstr = join("", $fdefs);
            $funcname = 'sdopx_func_' . $name;
            $output = "<?php if(!function_exists('{$funcname}')){"
                    . "function {$funcname}({$prefix},\$_sdopx){" . $fdefstr . "?>";

            return $output;
        }

    }

    //函数退出
    class Compile_Endfunction extends CompileBase {

        public function compile($args, $compiler) {
            return "<?php return; ?>";
        }

    }

    class Compile_Functionclose extends CompileBase {

        public function compile($args, $compiler) {
            list($openTag, $attr) = $this->closeTag($compiler, ['function']);
            if ($openTag == 'function') {
                $this->removeTKey($compiler, $attr);
            }
            return "<?php }} ?>";
        }

    }

}