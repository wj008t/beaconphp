<?php

namespace sdopx\libs {

    /**
     * 词法分词器
     */
    class Lexer {

        /**
         * @var Source 
         */
        private static $source;
        //规则集合
        private static $regexp = [];
        private static $maps = [];
        //标记栈
        private static $stack = [];
        private static $tag = null;
        private static $tagbegin = 0;
        private static $tree = null;

        //分析规则
        private static function analysis($tagname, $rules) {
            if (!is_array($rules)) {
                $mode = $rules;
                $endtags = NULL;
            } else {
                $mode = $rules[0];
                $endtags = isset($rules[1]) ? $rules[1] : NULL; //碰到后退出解析
            }
            if ($endtags !== NULL) {
                $end = end(self::$stack);
                if ($end !== FALSE) {
                    if (is_array($endtags)) {
                        if (!in_array($end, $endtags)) {
                            return;
                        }
                    } else {
                        if ($endtags != $end) {
                            return;
                        }
                    }
                }
            }
            $regexp = RulesManager::getRule($tagname);
            switch ($mode) {
                case 0:
                    self::$regexp[] = '\G\s*(' . $regexp . ')';
                    break;
                case 1:
                    self::$regexp[] = '\G(' . $regexp . ')';
                    break;
                case 4:
                    self::$regexp[] = '\G\s+(' . $regexp . ')';
                    break;
                case 2:
                case 3:
                case 5:
                    self::$regexp[] = '(' . $regexp . ')';
                    break;
                case 6:
                    self::$regexp[] = '\G\s*(' . $regexp . ')[^=]';
                    break;
                case 7:
                    self::$regexp[] = '\G\s*(' . $regexp . ')\s*' . preg_quote(RulesManager::$right_delimiter, '@');
                    break;
                default:
                    break;
            }
            self::$maps[] = array($tagname, $mode);
        }

        //解析下一组
        private static function toNext(&$next) {
            self::$regexp = [];
            self::$maps = [];
            if (isset($next)) {
                foreach ($next as $key => $rules) {
                    self::analysis($key, $rules);
                }
            }
        }

        //获得整个正则表达式
        private static function getRegexp() {
            return '@' . join('|', self::$regexp) . '@iS';
        }

        public static function getLine($offset) {
            return substr_count(self::$source->content, "\n", 0, $offset);
        }

        private static function match(Compile $compler, $offset = 0, $endon = 0, &$matches = []) {
            if ($offset >= $endon) {
                return false;
            }
            $regexp = self::getRegexp();
            $ret = preg_match($regexp, self::$source->content, $matches, PREG_OFFSET_CAPTURE, $offset);
            if ($ret == 0) {
                $compler->trigger_template_error('模板语法有误，无法解析，不能匹配的标记:' . self::$tag);
                return false;
            }
            $matches = array_filter($matches, function(&$val) {
                return $val[1] != -1;
            });
            if (!count($matches)) {
                $compler->trigger_template_error('模板语法有误，无法解析，不能匹配的标记:' . self::$tag);
                return false;
            }
            next($matches);
            $result = key($matches) - 1;
            if (isset(self::$maps[$result])) {
                list($val, $start) = current($matches);
                switch (self::$maps[$result][1]) {
                    case 2:
                        $end = $start;   //结束就是找到元素的开头位置
                        $start = $offset; //开始就是偏移位置
                        $val = substr(self::$source->content, $start, $end - $start);
                        break;
                    case 3:
                        $end = $start + strlen($val); //寻找尾部标记使用 
                        $start = $offset;
                        $val = substr(self::$source->content, $start, $end - $start);
                        break;
                    default:
                        $end = $start + strlen($val);
                        break;
                }
                $tagname = self::$maps[$result][0];
                $token = RulesManager::getToken($tagname);
                //echo self::$tag . ':' . $val . '<br>';
                return ['tag' => $tagname, 'value' => $val, 'start' => $start, 'end' => $end, 'token' => $token];
            }
            $compler->trigger_template_error('模板语法有误，无法解析，不能匹配的标记:' . self::$tag);
            return false;
        }

        //解析单个数据==
        public static function lexTpl(Source $source, Compile $compler) {
            self::$source = $source;
            self::$stack = [];
            if (self::$source->endon == 0) {
                $endon = self::$source->lenght;
            } else {
                $endon = self::$source->endon;
            }
            self::$tagbegin = self::$source->offset;
            if (self::$source->offset >= $endon) {
                return NULL;
            }
            RulesManager::reset($source->left_delimiter, $source->right_delimiter);
            self::$tree = $tree = array();
            $tag = RulesManager::Init;
            do {
                self::$tag = $tag;
                $next = RulesManager::getNext($tag);
                self::toNext($next);
                $data = self::match($compler, self::$source->offset, $endon);
                if ($data === false) {
                    return NULL;
                }
                $tag = $data['tag'];
                self::$source->offset = $data['end'];
                $data['node'] = end(self::$stack);
                //碰到尾部返回数据
                //print_r($data);
                if ($tag == RulesManager::Close_Tpl) {
                    $tree[] = $data;
                    return $tree;
                }
                //存在进栈
                $open = RulesManager::getOpen($tag);
                if ($open !== NULL) {
                    array_push(self::$stack, $open);
                }
                //离开栈
                $close = RulesManager::getClose($tag);
                if ($close != NULL) {
                    $endtag = end(self::$stack);
                    if (!in_array($endtag, $close)) {
                        $compler->trigger_template_error(RulesManager::getError($endtag));
                        return NULL;
                    }
                    array_pop(self::$stack);
                }
                $tree[] = $data;
                self::$tree = $tree;
            } while (true);
        }

        //解析HTML标记
        public static function lexHtml(Source $source, Compile $compler) {
            self::$source = $source;
            if (self::$source->offset >= self::$source->lenght) {
                return NULL;
            }
            RulesManager::reset($source->left_delimiter, $source->right_delimiter);
            $left_delimiter = preg_quote(RulesManager::$left_delimiter, '@');
            $ret = preg_match('@' . $left_delimiter . '@', self::$source->content, $matches, PREG_OFFSET_CAPTURE, self::$source->offset);
            //如果是在代码区域中
            if ($source->end_literal !== null && is_string($source->end_literal)) {
                $hasliteral = preg_match($source->end_literal, self::$source->content, $mache, PREG_OFFSET_CAPTURE, self::$source->offset);
                if ($hasliteral != 0 && ($ret == 0 || $mache[0][1] <= $matches[0][1])) {
                    $rstr = substr(self::$source->content, self::$source->offset, $mache[0][1] - self::$source->offset);
                    $lstart = self::$source->offset = $mache[0][1];
                    $strlen = strlen($mache[0][0]);
                    $lend = $lstart + $strlen;
                    self::$source->offset = $lend;
                    return array('code' => $rstr, 'next' => RulesManager::Init_Literal);
                }
            }
            if ($ret == 0) {
                $rstr = substr(self::$source->content, self::$source->offset);
                return array('code' => $rstr, 'next' => RulesManager::Finish);
            } else {
                $rstr = substr(self::$source->content, self::$source->offset, $matches[0][1] - self::$source->offset);
                self::$source->offset = $matches[0][1];
                $strlen = strlen($matches[0][0]);
                if (self::$source->offset + 1 + $strlen < self::$source->lenght) {
                    $ml = self::$source->content[self::$source->offset + $strlen];
                    switch ($ml) {
                        case '#':
                            $next = RulesManager::Init_Config;
                            break;
                        case '*':
                            $next = RulesManager::Init_Comment;
                            break;
                        default:
                            $next = RulesManager::Init;
                            break;
                    }
                }
                return array('code' => $rstr, 'next' => $next);
            }
        }

        //解析备注信息
        public static function lexComment(Source $source, Compile $compler) {
            self::$source = $source;
            if (self::$source->offset >= self::$source->lenght) {
                return NULL;
            }
            RulesManager::reset($source->left_delimiter, $source->right_delimiter);
            $left_delimiter = preg_quote(RulesManager::$left_delimiter . '*', '@');
            $ret = preg_match('@' . $left_delimiter . '@', self::$source->content, $matches, PREG_OFFSET_CAPTURE, self::$source->offset);
            if ($ret == 0) {
                return NULL;
            } else {
                self::$source->offset = $matches[0][1] + strlen($matches[0][0]);
                $right_delimiter = preg_quote('*' . RulesManager::$right_delimiter, '@');
                $ret = preg_match('@' . $right_delimiter . '@', self::$source->content, $matches, PREG_OFFSET_CAPTURE, self::$source->offset);
                if ($ret == 0) {
                    return NULL;
                }
                self::$source->offset = $matches[0][1] + strlen($matches[0][0]);
                return array('next' => 'html');
            }
        }

        //解析配置文件
        public static function lexConfig(Source $source, Compile $compler) {
            self::$source = $source;
            self::$stack = [];
            if (self::$source->endon == 0) {
                $endon = self::$source->lenght;
            } else {
                $endon = self::$source->endon;
            }
            self::$tagbegin = self::$source->offset;
            if (self::$source->offset >= $endon) {
                return NULL;
            }
            RulesManager::reset($source->left_delimiter, $source->right_delimiter);
            self::$tree = $tree = array();
            $tag = RulesManager::Init_Config;
            do {
                self::$tag = $tag;
                $next = RulesManager::getNext($tag);
                self::toNext($next);
                $data = self::match($compler, self::$source->offset, $endon);
                if ($data === false) {
                    return NULL;
                }
                $tag = $data['tag'];
                self::$source->offset = $data['end'];
                $data['node'] = end(self::$stack);
                //碰到尾部返回数据
                if ($tag == RulesManager::Close_Config) {
                    $tree[] = $data;
                    return $tree;
                }
                $tree[] = $data;
                self::$tree = $tree;
            } while (true);
        }

        //查找block块==
        public static function findBrock(Source $source, Compile $compler) {
            if ($source->blocks !== null) {
                return;
            }
            RulesManager::reset($source->left_delimiter, $source->right_delimiter);
            $left = preg_quote(RulesManager::$left_delimiter, '@');
            $right = preg_quote(RulesManager::$right_delimiter, '@');
            $block_stack = [];
            $blocks = [];
            $offset = 0;
            while ($offset < $source->lenght) {
                $item = [];
                $regexp = '@' . $left . '(block)\s+|' . $left . '(/block)\s*' . $right . '@iS';
                $ret = preg_match($regexp, $source->content, $matches, PREG_OFFSET_CAPTURE, $offset);
                if ($ret != 0) {
                    $matches = array_filter($matches, function(&$val) {
                        return $val[1] != -1;
                    });
                    next($matches);
                    list($val, $start) = current($matches);
                    $offset = $matches[0][1] + strlen($matches[0][0]);
                    if ($val == '/block') {
                        $item['end'] = $offset;
                        if (count($block_stack) <= 0) {
                            $compler->trigger_template_error('多余的关闭标记 ' . RulesManager::$left_delimiter . '/block' . RulesManager::$right_delimiter);
                            exit;
                        }
                        $temp = array_pop($block_stack);
                        $temp['end'] = $matches[0][1];
                        $temp['content'] = substr($source->content, $temp['start'], $temp['end'] - $temp['start']);
                        $blocks[] = $temp;
                        continue;
                    }
                } else {
                    break;
                }
                //解析属性值===
                while ($ret != 0) {
                    $regexp = '@\G(name)=\s*|\G(append|prepend|hide|nocache)\s*|\G(' . $right . ')@iS';
                    $ret = preg_match($regexp, $source->content, $matches, PREG_OFFSET_CAPTURE, $offset);
                    if ($ret != 0) {
                        $offset = $matches[0][1] + strlen($matches[0][0]);
                        $matches = array_filter($matches, function(&$val) {
                            return $val[1] != -1;
                        });
                        next($matches);
                        list($val, $start) = current($matches);
                        if ($val == 'name') {
                            $regexp = '@\G(\w+)\s*|\G\'(\w+)\'\s*|\G"(\w+)"\s*|\G(\$\w+)\s*|@iS';
                            $ret = preg_match($regexp, $source->content, $matches, PREG_OFFSET_CAPTURE, $offset);
                            if ($ret != 0) {
                                $offset = $matches[0][1] + strlen($matches[0][0]);
                                $matches = array_filter($matches, function(&$val) {
                                    return $val[1] != -1;
                                });
                                next($matches);
                                list($bval, $start) = current($matches);
                                $item['name'] = $bval;
                            }
                        } elseif ($val == RulesManager::$right_delimiter) {
                            $item['start'] = $offset;
                            $block_stack[] = $item;
                            break;
                        } else {
                            $item[$val] = true;
                        }
                    }
                }
            }
            $source->blocks = [];
            $tempblock = array_reverse($blocks);
            foreach ($tempblock as $block) {
                if (!isset($block['name'])) {
                    continue;
                }
                $name = $block['name'];
                if (isset($source->blocks[$name])) {
                    $source->blocks[$name][] = $block;
                } else {
                    $source->blocks[$name] = [$block];
                }
            }
        }

    }

}
