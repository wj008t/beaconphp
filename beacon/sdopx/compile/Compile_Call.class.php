<?php

namespace sdopx\compile {

    class Compile_Call extends CompileBase {

        public function compile($args, $compiler) {
            $name = isset($args['name']) ? trim($args['name'], '\'') : '';
            if (preg_match('@^\w+$@', $name) == 0) {
                $compiler->trigger_template_error('call 标签中的 name 属性 必须是 变量字符串格式。');
                return '';
            }
            unset($args['name']);
            $temp = [];
            foreach ($args as $_key => $_value) {
                if (is_int($_key)) {
                    $temp[] = "$_key=>$_value";
                } else {
                    $temp[] = "'$_key'=>$_value";
                }
            }
            $_params = '[' . implode(",", $temp) . ']';

            $funcname = 'sdopx_func_' . $name;
            if (isset(\sdopx\Sdopx::$functions[$name])) {
                $funcname = \sdopx\Sdopx::$functions[$name];
            }
            $_output = "<?php echo {$funcname}({$_params},\$_sdopx);?>";
            return $_output;
        }

    }

}