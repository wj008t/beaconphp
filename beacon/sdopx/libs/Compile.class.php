<?php

namespace sdopx\libs;

include_once SDOPX_DIR . 'plugins' . DS . 'sdopx_compiler.inc.php';

/**
 * 编译器
 */
class Compile {

    public $_tag_stack = [];
    //临时变量名称
    public static $temp_vkey = [];
    //临时变量前缀
    public static $temp_prefixs = [];
    //关闭编译
    public $closed = false;
    //缓存编译过的block 以备重名使用
    public $block_data = [];

    /**
     *
     * @var \sdopx\libs\Template 
     */
    public $tpl = null;

    /**
     *
     * @var Parser 
     */
    public $parser = null;

    /**
     *
     * @var /samao/sdopx/Sdopx 
     */
    public $sdopx = null;

    /**
     *
     * @var \sdopx\libs\Source 
     */
    public $source = null;

    public function __construct() {
        $this->parser = new Parser($this);
    }

    public function append(&$code, $temp) {
        if ($this->sdopx->compile_format >= 1) {
            $temp = trim($temp, "\t\n\r\0");
            if ($this->sdopx->compile_format >= 2 && preg_match('@^\s*<\?php@', $temp) && preg_match('@\?>\s*$@', $code)) {
                $code = preg_replace('@\?>\s*$@', '', $code) . preg_replace('@^\s*<\?php@', '', $temp);
            } elseif (preg_match('@^<\?php@', $temp) && preg_match('@\?>$@', $code)) {
                $code = preg_replace('@\?>$@', '', $code) . preg_replace('@^<\?php@', '', $temp);
            } else {
                $code.=$temp;
            }
        } else {
            $code.=$temp;
        }
    }

    public function compileTemplate(Template $tpl = null) {
        if ($tpl != null) {
            $this->sdopx = $tpl->sdopx;
            $this->tpl = $tpl;
            $this->source = $tpl->source;
        } else {
            if ($this->source == null) {
                die('错误');
            }
        }
        if (!RulesManager::$isload) {
            RulesManager::load();
        }
        $code = '';
        $i = 0;
        while ($i < 1000000) {
            $htitem = $this->parser->parsHtml($this->source);
            if ($htitem === NULL) {
                break;
            }
            if (!empty($htitem['code'])) {
                $tempcode = $htitem['code'];
                if ($this->sdopx->compile_format >= 3) {
                    $tempcode = preg_replace('@\n\s+@', "\n", $tempcode);
                    $tempcode = preg_replace('@\r\s+@', "\n", $tempcode);
                }
                if ($this->sdopx->compile_format >= 4) {
                    $tempcode = preg_replace('@>\s+<@', '><', $tempcode);
                }
                $this->append($code, $tempcode);
            }

            if ($htitem['next'] == RulesManager::Init_Literal) {
                $tplitem = $this->parser->parsLiteral($this->source);
                if ($tplitem == NULL) {
                    break;
                }
                if (Parser::CODE_TAG_CLOSE === $tplitem['map']) {
                    $class = '\\sdopx\\compile\\Compile_' . ucfirst($tplitem['name'] . 'close');
                    try {
                        if (class_exists($class)) {
                            $obj = new $class();
                            $temp = $obj->compile(null, $this);
                            $this->append($code, $temp);
                        }
                    } catch (Exception $e) {
                        $this->trigger_template_error("编译错误，没有找到({$tplitem['name']})标记的编译类。");
                    }
                }
            }
            if ($htitem['next'] == RulesManager::Init) {
                $tplitem = $this->parser->parsTpl($this->source);
                if ($tplitem == NULL) {
                    break;
                }
                switch ($tplitem['map']) {
                    case Parser::CODE_ASSIGN:
                        $this->append($code, '<?php ' . $tplitem['code'] . ';?>');
                        break;
                    case Parser::CODE_EXPRESS;
                        $this->append($code, '<?php echo ' . $tplitem['code'] . ';?>');
                        break;
                    case Parser::CODE_TAG:
                        $class = '\\sdopx\\compile\\Compile_' . ucfirst(str_replace(' ', '', $tplitem['name']));
                        try {
                            if (class_exists($class)) {
                                $obj = new $class();
                                $temp = $obj->compile($tplitem['args'], $this);
                            } else {
                                if (preg_match('@^(.*)else$@', $tplitem['name'], $arr)) {
                                    $class = '\\sdopx\\compile\\CompileTagelse';
                                    $tplitem['name'] = $arr[1];
                                } else {
                                    $class = '\\sdopx\\compile\\CompileTag';
                                }
                                $obj = new $class();
                                $temp = $obj->compile($tplitem['name'], $tplitem['args'], $this);
                            }
                            $this->append($code, $temp);
                        } catch (Exception $e) {
                            $this->trigger_template_error("编译错误，没有找到({$tplitem['name']})标记的编译类。");
                        }
                        break;
                    case Parser::CODE_TAG_CLOSE:
                        $class = '\\sdopx\\compile\\Compile_' . ucfirst($tplitem['name'] . 'close');
                        try {
                            if (class_exists($class)) {
                                $obj = new $class();
                                $temp = $obj->compile(null, $this);
                            } else {
                                $class = '\\sdopx\\compile\\CompileTagclose';
                                $obj = new $class();
                                $temp = $obj->compile($tplitem['name'], null, $this);
                            }
                            $this->append($code, $temp);
                        } catch (Exception $e) {
                            $this->trigger_template_error("编译错误，没有找到({$tplitem['name']})标记的编译类。");
                        }
                        break;
                    default:
                        break;
                }
            }
            if ($this->closed) {
                break;
            }
            if ($htitem['next'] == RulesManager::Init_Comment) {
                $cmitem = $this->parser->parsComment($this->source);
                if ($cmitem === NULL) {
                    break;
                }
            }
            if ($htitem['next'] == RulesManager::Init_Config) {
                $cmitem = $this->parser->parsConfig($this->source);
                if ($cmitem === NULL) {
                    break;
                }
                $obj = new \sdopx\compile\Compile_Config();
                $temp = $obj->compile($cmitem['code'], $this);
                $this->append($code, $temp);
            }
            if ($htitem['next'] == RulesManager::Finish) {
                break;
            }
            $i++;
        }
        $this->closed = true;
        return $code;
    }

    //编译版块数据===
    public function compileBlock($name) {
        if ($this->source->blocks === null) {
            Lexer::findBrock($this->source, $this);
        }
        $code = NULL;
        if (array_key_exists($name, $this->block_data)) {
            $code = $this->block_data[$name];
        } else {
            if ($this->tpl->parent != null) {
                $code = $this->tpl->parent->compiler->compileBlock($name);
                foreach ($this->tpl->func_files as $key => $value) {
                    $this->tpl->parent->func_files[$key] = $value;
                }
                foreach ($this->tpl->class_files as $key => $value) {
                    $this->tpl->parent->class_files[$key] = $value;
                }
                $this->block_data[$name] = $code;
            }
        }

        if (isset($this->source->blocks[$name])) {
            $args = $this->source->blocks[$name][0];
            $hide = isset($args['hide']) ? $args['hide'] : false;
            if ($hide && $code == NULL) {
                return NULL;
            }
            $prepend = isset($args['prepend']) ? $args['prepend'] : false;
            $append = isset($args['append']) ? $args['append'] : false;
            if (!($prepend || $append) && $code !== NULL) {
                return $code;
            }
            $offset = $this->source->offset;
            $closed = $this->closed;
            $this->source->offset = $args['start'];
            $this->source->endon = $args['end'];
            $this->closed = false;
            $output = $this->compileTemplate();
            $this->source->offset = $offset;
            $this->closed = $closed;
            if ($prepend && $code !== NULL) {
                return $code . $output;
            }
            if ($append && $code !== NULL) {
                return $output . $code;
            }
            return $output;
        }
        if ($code !== NULL) {
            return $code;
        }
        return NULL;
    }

    public function trigger_template_error($args = null, $line = null) {
        if (!isset($line)) {
            $line = $this->parser->taglineno;
        }
        $match = preg_split("/\n/", $this->source->content);
        $error_text = "模板编译错误了({$args}),模板文件:{$this->tpl->source->filepath} \n所在行:({$line}) \n" . $match[$line - 1];
        $e = new \sdopx\SdopxCompilerException($error_text);
        $e->line = $line;
        $e->source = trim(preg_replace('![\t\r\n]+!', ' ', $match[$line - 1]));
        $e->desc = $args;
        $e->template = $this->tpl->source->filepath;
        throw $e;
    }

}
