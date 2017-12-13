<?php

namespace sdopx\compile {

    use \sdopx\libs\Compile;

    class CompileBase {

        /**
         * 添加私有变量栈
         * @param \sdopx\libs\Compile $compiler
         * @param array $keys
         */
        public function addTKey($compiler, $attrs) {
            foreach ($attrs as $key => $value) {
                if (isset(Compile::$temp_vkey[$key])) {
                    Compile::$temp_vkey[$key][] = $value;
                } else {
                    Compile::$temp_vkey[$key] = [$value];
                }
            }
        }

        //获得一个临时键名前缀==
        public function getTempPrefix($prefix) {
            if (isset(Compile::$temp_prefixs[$prefix])) {
                Compile::$temp_prefixs[$prefix]+=1;
                return $prefix . Compile::$temp_prefixs[$prefix];
            } else {
                Compile::$temp_prefixs[$prefix] = 0;
                return $prefix;
            }
        }

        public function removeTKey($compiler, $attrs) {
            foreach ($attrs as $key => $value) {
                if (!isset(Compile::$temp_vkey[$key])) {
                    $compiler->trigger_template_error("临时变量不存在 \$" . $key . "");
                } else {
                    $end = array_pop(Compile::$temp_vkey[$key]);
                    if ($end != $value) {
                        $compiler->trigger_template_error("临时变量不存在 \$" . $key . "");
                    }
                    if (count(Compile::$temp_vkey[$key]) == 0) {
                        unset(Compile::$temp_vkey[$key]);
                    }
                }
            }
        }

        public function openTag($compiler, $openTag, $data = null) {
            array_push($compiler->_tag_stack, array($openTag, $data));
        }

        public function endTag($compiler) {
            if (count($compiler->_tag_stack) > 0) {
                list($_openTag, $_data) = end($compiler->_tag_stack);
                if (is_null($_data)) {
                    return $_openTag;
                } else {
                    return $_data;
                }
            }
            $compiler->trigger_template_error("错误的关闭标签，可能是自定义标签不是配对模式。", $compiler->parser->taglineno);
            return;
        }

        public function testEndTag($compiler, $expectedTag) {
            if (count($compiler->_tag_stack) > 0) {
                list($_openTag, $_data) = end($compiler->_tag_stack);
                if (in_array($_openTag, (array) $expectedTag)) {
                    return true;
                }
            }
            return false;
        }

        public function testInTag($compiler, $expectedTag) {
            $len = count($compiler->_tag_stack);
            if ($len > 0) {
                for ($i = $len - 1; $i >= 0; $i--) {
                    $temp = $compiler->_tag_stack[$i];
                    if (in_array($temp[0], (array) $expectedTag)) {
                        return true;
                    }
                }
            }
            return false;
        }

        public function closeTag($compiler, $expectedTag) {
            if (count($compiler->_tag_stack) > 0) {
                list($_openTag, $_data) = array_pop($compiler->_tag_stack);
                if (in_array($_openTag, (array) $expectedTag)) {
                    if (is_null($_data)) {
                        return $_openTag;
                    } else {
                        return $_data;
                    }
                }
                $compiler->trigger_template_error("没有关闭标签 {$compiler->sdopx->left_delimiter}" . $_openTag . "{$compiler->sdopx->right_delimiter} tag");
                return;
            }
            $tagnames = [];
            foreach ((array) $expectedTag as $tag) {
                $tagnames[] = $compiler->sdopx->left_delimiter . '/' . $tag . $compiler->sdopx->right_delimiter;
            }
            $compiler->trigger_template_error(join(',', $tagnames) . "错误的关闭标签", $compiler->parser->taglineno);
            return;
        }

    }

    class Compile_Break extends CompileBase {

        public function compile($args, $compiler) {
            if ($this->testInTag($compiler, ['foreach', 'for'])) {
                return "<?php break; ?>";
            }
            $compiler->trigger_template_error("{break} 标记必须在 foreach 或者 for 标记中！", $compiler->parser->taglineno);
        }

    }

    class Compile_Continue extends CompileBase {

        public function compile($args, $compiler) {
            if ($this->testInTag($compiler, ['foreach', 'for'])) {
                return "<?php continue; ?>";
            }
            $compiler->trigger_template_error("{continue} 标记必须在 foreach 或者 for 标记中！", $compiler->parser->taglineno);
        }

    }

}