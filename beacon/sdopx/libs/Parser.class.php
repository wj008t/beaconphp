<?php

namespace sdopx\libs {

    /**
     * 语法分析器
     */
    class Parser {

        const CODE_HTML = 'html';
        const CODE_EXPRESS = 'exp';
        const CODE_ASSIGN = 'assign';
        const CODE_CONFIG = 'conf';
        const CODE_TAG = 'tag';
        const CODE_TAG_CLOSE = 'tag_close';
        const CODE_BLOCK = 'block';
        const CODE_EMPTY = 'empty';
        const CODE_COMMENT = 'comment';

        private $lexdata; //零时数据整理
        public $taglineno = 0;
        public $compiler = null;

        public function __construct(Compile $compiler) {
            $this->compiler = $compiler;
        }

        public function parsHtml(Source $source) {
            $item = Lexer::lexHtml($source, $this->compiler);
            if ($item == NULL) {
                return NULL;
            }
            $item['map'] = Parser::CODE_HTML;
            return $item;
        }

        public function parsComment(Source $source) {
            $item = Lexer::lexComment($source, $this->compiler);
            if ($item == NULL) {
                return NULL;
            }
            $item['map'] = Parser::CODE_COMMENT;
            return $item;
        }

        public function parsTpl(Source $source) {
            $this->taglineno = $source->getLine();
            $items = Lexer::lexTpl($source, $this->compiler);
            if ($items == NULL) {
                return NULL;
            }
            $this->lexdata = $items;
            $ret = $this->callnext();
            if ($ret === false) {
                return NULL;
            }
            if ($ret['map'] == Parser::CODE_EXPRESS) {
                $retx = $this->pars_express();
                if ($retx === false) {
                    return NULL;
                }
                $value = $ret['code'] . $retx['code'];
                $this->pars_modifier($value);
                $ret['code'] = $value;
            }
            if ($ret['map'] == Parser::CODE_EXPRESS) {
                if (isset($items[1]) && $items[1]['tag'] == RulesManager::Variable) {
                    for ($i = 2, $len = count($items); $i < $len; $i++) {
                        if ($items[$i]['tag'] == RulesManager::Assign) {
                            $ret['map'] = Parser::CODE_ASSIGN;
                        }
                    }
                }
            }
            return $ret;
        }

        public function parsLiteral(Source $source) {
            $this->taglineno = $source->getLine();
            return ['map' => Parser::CODE_TAG_CLOSE, 'name' => 'literal', 'node' => 'in_tpl'];
        }

        public function parsConfig(Source $source) {
            $this->taglineno = $source->getLine();
            $items = Lexer::lexConfig($source, $this->compiler);
            if ($items == NULL) {
                return NULL;
            }
            $item = next($items);
            $ret['code'] = $item['value'];
            $ret['map'] = Parser::CODE_CONFIG;
            return $ret;
        }

        //下一位
        private function callnext() {
            $item = next($this->lexdata);
            if (!empty($item['token'])) {
                return call_user_func(array($this, 'pars_' . $item['token']), $item);
            }
            return false;
        }

        //解析代码
        private function pars_code($item) {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
                'code' => $item['value'],
                'node' => $item['node'],
            );
            return $temp;
        }

        //解析符号
        private function pars_symbol($item) {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
            );
            $value = trim($item['value']);
            $value = str_ireplace('ge', '>=', $value);
            $value = str_ireplace('le', '<=', $value);
            $value = str_ireplace('gt', '>', $value);
            $value = str_ireplace('lt', '<', $value);
            $value = str_ireplace('mod', '%', $value);
            $value = str_ireplace('and', '&&', $value);
            $value = str_ireplace('or', '||', $value);
            $value = str_ireplace('xor', '^', $value);
            $value = ' ' . trim($value) . ' ';
            $temp['code'] = $value;
            $temp['node'] = $item['node'];
            return $temp;
        }

        //解析配置文件
        private function pars_config($item) {
            $temp = array(
                'map' => Parser::CODE_CONFIG,
            );
            if ($item === false || $item['map'] != RulesManager::ConfigKey) {
                return false;
            }
            $temp['code'] = '$smtpl->_svar[\'config\'][\'' . trim($item['value']) . '\']';
            $temp['node'] = $item['node'];
            $item = next($this->lexdata);
            if ($item === false || $item['map'] != RulesManager::Close_Config) {
                return false;
            }
            return $temp;
        }

        //解析关闭符
        private function pars_tagclose($item) {
            $temp = array(
                'map' => Parser::CODE_TAG_CLOSE,
            );
            $temp['name'] = ltrim(trim($item['value']), '/');
            $temp['node'] = $item['node'];
            $item = next($this->lexdata);
            if ($item === false || $item['tag'] != RulesManager::Close_Tpl) {
                return false;
            }
            return $temp;
        }

        //解析属性值
        private function pars_attr($item) {
            $temp = array(
                'map' => Parser::CODE_EMPTY,
            );
            $temp['name'] = rtrim(trim($item['value']), '=');
            $temp['node'] = $item['node'];
            return $temp;
        }

        //解析表达式==
        private function pars_express() {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
                'code' => '',
                'node' => '',
            );
            $text = '';
            $node = '';
            while (true) {
                $ret = $this->callnext();
                if (isset($ret['node'])) {
                    if ($ret['node'] == RulesManager::In_ModifiersFunc && $ret['map'] == 'exp' && $ret['code'] == ':') {
                        prev($this->lexdata);
                        $temp['code'] = $text;
                        $temp['node'] = $node;
                        return $temp;
                    }
                }
                if ($ret === false || $ret['map'] != Parser::CODE_EXPRESS) {
                    prev($this->lexdata);
                    $temp['code'] = $text;
                    $temp['node'] = $node;
                    return $temp;
                }
                $text.=$ret['code'];
                $node = $ret['node'];
            }
            return false;
        }

        //解析变量==
        private function pars_var($item) {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
            );
            $value = trim($item['value']);
            $value = preg_replace_callback('@^\$(\w+)@', function($matches) {
                $key = $matches[1];
                if (!isset(Compile::$temp_vkey[$key])) {
                    return '$_sdopx->_book[\'' . $key . '\']';
                }
                $keyname = end(Compile::$temp_vkey[$key]);
                return str_replace('@key', $key, $keyname);
            }, $value);
            $temp['code'] = $value;
            $temp['node'] = $item['node'];
            return $temp;
        }

        private function pars_varkey($item) {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
            );
            $value = preg_replace('@^\.(\w+)@', '[\'$1\']', trim($item['value']));
            $temp['code'] = $value;
            $temp['node'] = $item['node'];
            return $temp;
        }

        //√解析单引号字符串==
        private function pars_dyh_string($item) {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
            );
            $text = '\'';
            $node = '';
            $item = next($this->lexdata);
            if ($item === false || false == ($item['tag'] == RulesManager::SingleQuotesString || $item['tag'] == RulesManager::Close_SingleQuotes)) {
                return false;
            }
            if ($item['tag'] == RulesManager::SingleQuotesString) {
                $text.=$item['value'];
                $node = $item['node'];
                $item = next($this->lexdata);
                if ($item === false || $item['tag'] != RulesManager::Close_SingleQuotes) {
                    return false;
                }
            }
            $text .= '\'';
            $temp['code'] = $text;
            $temp['node'] = $node;
            return $temp;
        }

        //√解析双引号字符串==
        private function pars_syh_string($item) {
            $temp = array(
                'map' => Parser::CODE_EXPRESS,
                'node' => '',
            );
            if ($item['tag'] == RulesManager::Open_DoubleQuotes) {
                $text = '\'';
            }
            if ($item['tag'] == RulesManager::Close_Delimiter) {
                $text = '.\'';
            }
            $item = next($this->lexdata);
            if ($item === false ||
                    ($item['tag'] == RulesManager::DoubleQuotesString ||
                    $item['tag'] == RulesManager::Close_DoubleQuotes ||
                    $item['tag'] == RulesManager::Open_Delimiter) == false) {
                return false;
            }
            if ($item['tag'] == RulesManager::DoubleQuotesString) {
                $tpval = str_replace('\'', '\\\'', $item['value']);
                $text.=str_replace('\"', '"', $tpval);
                $item = next($this->lexdata);
                if ($item === false || ($item['tag'] == RulesManager::Close_DoubleQuotes ||
                        $item['tag'] == RulesManager::Open_Delimiter) == false) {
                    return false;
                }
            }
            if ($item['tag'] == RulesManager::Close_DoubleQuotes) {
                $text .= '\'';
                $temp['code'] = $text;
                $temp['node'] = $item['node'];
                return $temp;
            }
            $item = next($this->lexdata);
            if ($item['tag'] == RulesManager::Close_Delimiter) {
                $text .= '\'';
            } else {
                $text .= '\'.';
            }
            $temp['node'] = $item['node'];
            prev($this->lexdata);
            $temp['code'] = $text;
            //print_r($temp);
            return $temp;
        }

        private function pars_array_open($item) {
            if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                $temp = array(
                    'map' => Parser::CODE_EXPRESS,
                    'code' => 'array(',
                );
            } else {
                $temp = array(
                    'map' => Parser::CODE_EXPRESS,
                    'code' => '[',
                );
            }
            $temp['node'] = $item['node'];
            return $temp;
        }

        private function pars_array_close($item) {
            if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                $temp = array(
                    'map' => Parser::CODE_EXPRESS,
                    'code' => ')',
                );
            } else {
                $temp = array(
                    'map' => Parser::CODE_EXPRESS,
                    'code' => ']',
                );
            }
            $temp['node'] = $item['node'];
            return $temp;
        }

        //过滤器----
        private function pars_modifier(&$value) {
            static $i = 0;
            $i++;
            $item = next($this->lexdata);
            if ($item['tag'] != RulesManager::ModifiersFunc) {
                prev($this->lexdata);
                return false; //没找到
            }

            $params = array($value);
            $name = ltrim(trim($item['value']), '|');

            while (true) {
                $item = next($this->lexdata);
                if ($item['tag'] != RulesManager::Colons) {
                    prev($this->lexdata);
                    break;
                }
                $val = $this->pars_express_item(false);
                if ($val === false) {
                    break;
                }
                $params[] = $val;
            }
            $callback = 'modifiercompiler_' . $name;
            if (function_exists($callback) || $this->compiler->sdopx->loadPlugin($callback, true) !== false) {
                $value = call_user_func($callback, $params, $this->compiler);
            } else {
                $callback = 'modifier_' . $name;
                $filename = $this->compiler->sdopx->loadPlugin($callback);
                if ($filename !== false) {
                    $this->compiler->tpl->func_files[$filename] = $callback;
                }
                $value = $callback . '( ' . join($params, ' , ') . ' )';
            }
            $this->pars_modifier($value);
            return true;
        }

        private function pars_express_item($nocake = true) {
            $ret = $this->pars_express();
            if ($ret === false) {
                return false;
            }
            $value = $ret['code'];
            if ($nocake) {
                $this->pars_modifier($value);
            }
            return $value;
        }

        //√解析标记
        private function pars_tagname($item) {
            $temp = array(
                'map' => Parser::CODE_TAG,
            );
            //获得标记名称
            $temp['name'] = trim($item['value']);
            //获得标记参数
            $temp['args'] = array();

            while (true) {
                $item = next($this->lexdata);
                if ($item['tag'] == RulesManager::Close_TagAttr) {
                    continue;
                }
                if ($item['tag'] == RulesManager::Close_Tpl) {
                    $temp['node'] = $item['node'];
                    return $temp;
                }
                if ($item['tag'] == RulesManager::Open_TagAttr) {
                    $ret = $this->pars_attr($item);
                    if ($ret === false) {
                        return false;
                    }
                    $name = trim($ret['name']);
                    $value = $this->pars_express_item(false);
                    if ($value === false) {
                        return false;
                    }
                    $temp['args'][$name] = $value;
                    continue;
                } elseif ($item['tag'] == RulesManager::Single_TagAttr) {
                    $ret = $this->pars_attr($item);
                    if ($ret === false) {
                        return false;
                    }
                    $name = trim($ret['name']);
                    $temp['args'][$name] = 'true';
                    continue;
                } else {
                    prev($this->lexdata);
                    $value = $this->pars_express_item(false);
                    if ($value === false) {
                        return false;
                    }
                    if (!empty($value)) {
                        $temp['args']['code'] = $value;
                    }
                    $temp['node'] = $item['node'];
                    return $temp;
                }
            }
            $temp['node'] = $item['node'];
            return $temp;
        }

        private function pars_closetpl($item) {
            $temp = array(
                'map' => Parser::CODE_EMPTY,
            );
            if ($item === false || $item['tag'] != RulesManager::Close_Tpl) {
                return false;
            }
            $temp['node'] = $item['node'];
            return $temp;
        }

        private function pars_empty($item) {
            $temp = array(
                'map' => Parser::CODE_EMPTY,
            );
            $temp['node'] = $item['node'];
            return $temp;
        }

        //打开区块
        private function pars_block_open($item) {
            $temp = array(
                'map' => Parser::CODE_BLOCK,
            );
            $temp['name'] = 'block';
            $temp['args'] = array();
            $xtemp = end($this->blocks_stack);
            if ($xtemp !== FALSE) {
                $temp['map'] = Parser::CODE_EMPTY;
            }
            while (true) {
                $item = next($this->lexdata);
                if ($item['map'] == RulesManager::Close_TagAttr) {
                    continue;
                }
                if ($item['map'] == RulesManager::Close_Tpl) {
                    $temp['start'] = $item['end'];
                    $this->blocks_stack[] = $temp;
                    return $temp;
                }
                if ($item['map'] == RulesManager::Open_TagAttr) {
                    $ret = $this->pars_attr($item);
                    if ($ret === false) {
                        return false;
                    }
                    $name = $ret['name'];
                    $value = $this->pars_express_item(false);
                    if ($value === false) {
                        return false;
                    }
                    $temp['args'][$name] = $value;
                    continue;
                } elseif ($item['map'] == RulesManager::Single_TagAttr) {
                    $ret = $this->pars_attr($item);
                    if ($ret === false) {
                        return false;
                    }
                    $name = $ret['name'];
                    $temp['args'][$name] = 'true';
                    continue;
                }
            }
        }

        private function pars_block_close($item) {
            $temp = end($this->blocks_stack);
            if ($temp === FALSE) {
                return false;
            }
            array_pop($this->blocks_stack);
            $temp['map'] = Parser::CODE_EMPTY;
            $temp['name'] = 'block';
            $temp['end'] = $item['start'];
            if (!$temp['args'] || !isset($temp['args']['name'])) {
                return false;
            }
            $blockname = $temp['args']['name'];
            $this->blocks[$blockname] = $temp; //重名就是后面那个
            return $temp;
        }

    }

}