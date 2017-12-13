<?php

namespace sdopx\compile {

    class CompileTag extends CompileBase {

        //获取用户插件
        private function getClassPlugs($class, $tagname, $compiler, &$isblock = false, &$callback = '') {
            if (empty($class)) {
                return false;
            }
            $classname = 'Sdopx_' . compile_to_camel($class);
            $class_exists = class_exists($classname);
            if (!$class_exists) {
                $filename = $compiler->sdopx->loadPlugin('sdopx_' . $class . '.class');
                if (!$filename) {
                    return false;
                }
                @require_once $filename;
                $compiler->tpl->class_files[$filename] = $classname;
            }
            if (!class_exists($classname)) {
                return false;
            }
            $isblock = 0;
            if (is_callable($classname . '::sdopx_' . $tagname)) {
                $callback = $classname . '::sdopx_' . $tagname;
                if (method_exists($classname, 'sdopx_' . $tagname)) {
                    $cref = new \ReflectionMethod($classname, 'sdopx_' . $tagname);
                    $docs = $cref->getDocComment();
                    if (preg_match('#@sdopx\s+\[block\=loop\]#i', $docs, $macth) > 0) {
                        $isblock = 2;
                    } elseif (preg_match('#@sdopx\s+\[block\=once\]#i', $docs, $macth) > 0) {
                        $isblock = 1;
                    }
                }
                return true;
            }
            return false;
        }

        public function compile($name, $args, $compiler) {
            $tagname = $name;
            $names = explode(':', $name, 2);
            $class = null;
            if (isset($names[1])) {
                $tagname = $names[1];
                $class = $names[0];
            }
            //如果类为空
            if (empty($class)) {
                $classname = 'Sdopx_Compile_' . compile_to_camel($name);
                if (class_exists($classname)) {
                    $obj = new $classname();
                    return $obj->compile($args, $compiler);
                }
                $filename = $compiler->sdopx->loadPlugin('sdopx_compile_' . $name . '.class');
                if (!empty($filename)) {
                    @require_once $filename;
                }
                if (class_exists($classname)) {
                    $obj = new $classname();
                    return $obj->compile($args, $compiler);
                }
            }
            //处理自定义的
            $isblock = 0;
            $callback = '';
            if (!empty($class)) {
                $this->getClassPlugs($class, $tagname, $compiler, $isblock, $callback);
            } else {
                $callback = "sdopx_block_{$tagname}";
                $func_exists = function_exists($callback);
                if (!$func_exists) {
                    $filename = $compiler->sdopx->loadPlugin($callback);
                    if (!empty($filename)) {
                        $compiler->tpl->func_files[$filename] = $callback;
                    }
                }
                if ($func_exists || $filename) {
                    $isblock = 1;
                } else {
                    $isblock = 0;
                    if (isset(\sdopx\Sdopx::$functions[$tagname])) {
                        $callback = \sdopx\Sdopx::$functions[$tagname];
                    } else {
                        $callback = "sdopx_func_{$tagname}";
                        $func_exists = function_exists($callback);
                        if (!$func_exists) {
                            $filename = $compiler->sdopx->loadPlugin($callback);
                            if ($filename) {
                                $compiler->tpl->func_files[$filename] = $callback;
                            }
                        }
                    }
                }
            }

            if ($isblock == 2) {
                $temp = [];
                foreach ($args as $_key => $_value) {
                    if ($_key == 'loop_item' || $_key == 'loop_key' || $_key == 'loop_count') {
                        continue;
                    }
                    if (is_int($_key)) {
                        $temp[] = "$_key=>$_value";
                    } else {
                        $temp[] = "'$_key'=>$_value";
                    }
                }
                $_params = '[' . implode(",", $temp) . ']';
                $prefix = $this->getTempPrefix('$_temp'); //获得一个临时变量
                $item = isset($args['loop_item']) ? trim($args['loop_item'], '\'"') : 'rs';
                $key = isset($args['loop_key']) ? trim($args['loop_key'], '\'"') : '';
                $count = isset($args['loop_count']) ? trim($args['loop_count'], '\'"') : '';
                if (preg_match('@^\w+$@', $item) == 0) {
                    $compiler->trigger_template_error($name . ' 标签中的 item 属性 必须是 变量字符串格式。');
                    return '';
                }
                if (!empty($key) && preg_match('@^\w+$@', $key) == 0) {
                    $compiler->trigger_template_error($name . ' 标签中的 key 属性 必须是 变量字符串格式。');
                    return '';
                }
                if (!empty($count) && preg_match('@^\w+$@', $count) == 0) {
                    $compiler->trigger_template_error($name . ' 标签中的 count 属性 必须是 变量字符串格式。');
                    return '';
                }
                $vars = [];
                $vars[$item] = $prefix . '_@key';
                $vars['index'] = $prefix . '_@key';
                $_output = "<?php\n{$prefix} = {$callback}({$_params},\$_sdopx); \n";
                $_output.="if(is_array({$prefix})){ \n";
                $_output.="if(isset({$prefix}['head'])){echo {$prefix}['head'];}\n";
                $_output.="{$prefix}_loop=(isset({$prefix}['data'])&& is_array({$prefix}['data']))?{$prefix}['data']:[]; \n";
                //添加数量
                if (!empty($count)) {
                    $vars[$count] = $prefix . '_@key';
                    $_output .= "{$prefix}_{$count}=count({$prefix}_loop); \n if({$prefix}_{$count}>0){\n";
                } else {
                    $_output .= "if(count({$prefix}_loop)>0){\n";
                }
                $_output .= "{$prefix}_index=-1;\n";
                //添加键名
                if (!empty($key)) {
                    $vars[$key] = $prefix . '_@key';
                    $_output .= "foreach({$prefix}_loop as {$prefix}_{$key} => {$prefix}_{$item}){{$prefix}_index++;?>";
                } else {
                    $_output .= "foreach({$prefix}_loop as {$prefix}_{$item}){{$prefix}_index++;?>";
                }
                //打开标记---
                $this->addTKey($compiler, $vars);
                $this->openTag($compiler, $name, [$name, $vars, $prefix, 'tag_loop']);
            } elseif ($isblock == 1) {
                $temp = [];
                foreach ($args as $_key => $_value) {
                    if ($_key == 'assign') {
                        continue;
                    }
                    if (is_int($_key)) {
                        $temp[] = "$_key=>$_value";
                    } else {
                        $temp[] = "'$_key'=>$_value";
                    }
                }
                $_params = '[' . implode(",", $temp) . ']';
                $prefix = $this->getTempPrefix('$_temp'); //获得一个临时变量
                $item = isset($args['assign']) ? trim($args['assign'], '\'"') : 'rs';
                if (preg_match('@^\w+$@', $item) == 0) {
                    $compiler->trigger_template_error($name . ' 标签中的 item 属性 必须是 变量字符串格式。');
                    return '';
                }
                $vars = [];
                $vars[$item] = $prefix . '_@key';
                $vars['index'] = $prefix . '_@key';
                $_output = "<?php\n{$prefix} = {$callback}({$_params},\$_sdopx); \n";
                $_output.="if(is_array({$prefix})){ \n";
                $_output.="if(isset({$prefix}['head'])){echo {$prefix}['head'];}\n";
                $_output.="{$prefix}_{$item}=(isset({$prefix}['data'])?{$prefix}['data']:null); \n";
                //添加数量
                $_output .= "if(!empty({$prefix}_{$item})){?>";
                //打开标记---
                $this->addTKey($compiler, $vars);
                $this->openTag($compiler, $name, [$name, $vars, $prefix, 'tag_once']);
            } else {
                $temp = [];
                foreach ($args as $_key => $_value) {
                    if (is_int($_key)) {
                        $temp[] = "$_key=>$_value";
                    } else {
                        $temp[] = "'$_key'=>$_value";
                    }
                }
                $_params = '[' . implode(",", $temp) . ']';
                $_output = "<?php echo {$callback}({$_params},\$_sdopx);?>";
            }
            return $_output;
        }

    }

    class CompileTagelse extends CompileBase {

        public function compile($name, $args, $compiler) {
            list($openTag, $vars, $prefix, $type) = $this->endTag($compiler);
            if (isset($type) && $type == 'tag_loop') {
                $this->closeTag($compiler, [$name]);
                $this->removeTKey($compiler, $vars);
                $this->openTag($compiler, $name . 'else', [$name . 'else', [], $prefix, $type]);
                $_output = "<?php }\n}else{?>";
                return $_output;
            } elseif (isset($type) && $type == 'tag_once') {
                $this->closeTag($compiler, [$name]);
                $this->removeTKey($compiler, $vars);
                $this->openTag($compiler, $name . 'else', [$name . 'else', [], $prefix, $type]);
                $_output = "<?php }else{?>";
                return $_output;
            }
            return '';
        }

    }

    class CompileTagclose extends CompileBase {

        public function compile($name, $args, $compiler) {
            list($openTag, $vars, $prefix, $type) = $this->endTag($compiler);
            if (!isset($type) || !($type == 'tag_loop' || $type == 'tag_once')) {
                $classname = 'Sdopx_Compile_' . compile_to_camel($name);
                if (class_exists($classname)) {
                    $obj = new $classname();
                    return $obj->compile($args, $compiler);
                }
            } else {
                list($openTag, $vars, $prefix, $type) = $this->closeTag($compiler, [$name, $name . 'else']);
                if ($type == 'tag_loop') {
                    $this->removeTKey($compiler, $vars);
                    $_output = "<?php }\n}\n";
                    if ($openTag == $name . 'else') {
                        $_output = "<?php }\n";
                    }
                    $_output.="if(isset({$prefix}['foot'])){ echo {$prefix}['foot'];}}?>";
                    return $_output;
                } elseif ($type == 'tag_once') {
                    $this->removeTKey($compiler, $vars);
                    $_output = "<?php }\n";
                    $_output.="if(isset({$prefix}['foot'])){ echo {$prefix}['foot'];}}?>";
                    return $_output;
                } else {
                    $compiler->trigger_template_error($name . '错误的关闭标签！');
                }
            }
        }

    }

}