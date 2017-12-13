<?php

namespace sdopx\compile {

    class Compile_Block extends CompileBase {

        /**
         * @param array $args
         * @param \sdopx\libs\Compile $compiler
         * @return string
         */
        public function compile($args, $compiler) {
            $name = isset($args['name']) ? trim($args['name'], '\'') : '';
            $hide = isset($args['hide']) ? $args['hide'] : false;
            $append = isset($args['append']) ? $args['append'] : false;
            $prepend = isset($args['prepend']) ? $args['prepend'] : false;
            if (preg_match('@^\w+$@', $name) == 0) {
                $compiler->trigger_template_error('call 标签中的 name 属性 必须是 变量字符串格式。');
                return '';
            }
            $offset = $compiler->source->offset;
            $code = NULL;
            if (array_key_exists($name, $compiler->block_data)) {
                $code = $compiler->block_data[$name];
            } else {
                if ($compiler->tpl->parent != null) {
                    $code = $compiler->tpl->parent->compiler->compileBlock($name);
                    foreach ($compiler->tpl->func_files as $key => $value) {
                        $compiler->tpl->parent->func_files[$key] = $value;
                    }
                    foreach ($compiler->tpl->class_files as $key => $value) {
                        $compiler->tpl->parent->class_files[$key] = $value;
                    }
                    $compiler->block_data[$name] = $code;
                }
            }

            //如果没有继续执行本次===
            if ($code === NULL) {
                if ($hide) {
                    //如果隐藏跳到尾部==
                    $compiler->source->moveBlockToEnd($compiler, $name, $offset);
                }
                $this->openTag($compiler, 'block', ['block', '']);
                return '';
            } else {
                //代码加载再前面
                if (!($prepend || $append)) {
                    $compiler->source->moveBlockToEnd($compiler, $name, $offset);
                }
                //加在尾部
                if ($append) {
                    $this->openTag($compiler, 'block', ['block', $code]);
                    return '';
                }
                $this->openTag($compiler, 'block', ['block', '']);
                return $code;
            }
        }

    }

    class Compile_Blockclose extends CompileBase {

        public function compile($args, $compiler) {
            list($name, $code) = $this->closeTag($compiler, ['block']);
            return $code;
        }

    }

}