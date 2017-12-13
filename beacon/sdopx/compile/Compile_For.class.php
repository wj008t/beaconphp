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
    class Compile_For extends CompileBase {

        public function compile($args, $compiler) {
            $start = isset($args['start']) ? $args['start'] : 0;
            $to = isset($args['to']) ? $args['to'] : 0;
            $step = isset($args['step']) ? $args['step'] : 1;
            $var = isset($args['var']) ? $args['var'] : 'index';
            if (preg_match('@^\w+$@', $var) == 0) {
                $compiler->trigger_template_error('for 标签中的 var 属性 必须是 变量字符串格式。');
                return '';
            }
            //获得一个变量前缀--
            $prefix = $this->getTempPrefix('$temp');
            $vars = [];
            $vars['total'] = $prefix . '_@key';
            $vars[$var] = $prefix . '_@key';
            $this->openTag($compiler, 'for', ['for', $vars, $prefix]);
            $this->addTKey($compiler, $vars);
            $_output = "<?php {$prefix}_total=0;";
            if (empty($start) && empty($to) && !empty($args['code'])) {
                $_output .= "for({$args['code']}){ {$prefix}_total++;?>";
            } else {
                if (empty($start)) {
                    $compiler->trigger_template_error('for 标签中缺少 start');
                }
                if (empty($to)) {
                    $compiler->trigger_template_error('for 标签中缺少 to');
                }
                $_output .= "for({$prefix}_{$var}={$start},\$temp={$start}<{$to}; (\$temp?{$prefix}_{$var}<={$to}:{$prefix}_{$var}>={$to});{$prefix}_{$var}+=(\$temp?{$step}:-{$step})){ {$prefix}_total++;?>";
            }
            return $_output;
        }

    }

    class Compile_Forelse extends CompileBase {

        public function compile($argsc, $compiler) {
            list($openTag, $vars, $prefix) = $this->closeTag($compiler, ['for']);
            $this->openTag($compiler, 'forelse', ['forelse', $vars, $prefix]);
            return "<?php }\nif ({$prefix}_total<=0) {?>";
        }

    }

    class Compile_Forclose extends CompileBase {

        public function compile($args, $compiler) {
            list($openTag, $vars, $prefix) = $this->closeTag($compiler, ['for', 'forelse']);
            $this->removeTKey($compiler, $vars);
            return "<?php } ?>";
        }

    }

}