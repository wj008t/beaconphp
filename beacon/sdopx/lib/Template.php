<?php

/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/7/17
 * Time: 12:28
 */

namespace sdopx\lib;

use sdopx\Sdopx;


class Template
{

    public static $complie_cache = [];

    public $sdopx = null;
    public $tplId = null;

    /**
     * 父模板
     * @var Template
     */
    public $parent = null;
    public $tplname = null;
    public $extends_tplId = [];
    public $recompilation = false;


    /**
     * @var Compiler
     */
    private $compiler = null;
    /**
     * @var Source
     */
    private $source = null;

    private $property = [];
    private $namespace = [];

    public function __construct($tplname = null, Sdopx $sdopx = null, Template $parent = null)
    {
        $this->tplname = $tplname;
        $this->sdopx = empty($sdopx) ? $this : $sdopx;
        $this->parent = $parent;
        if ($tplname !== null) {
            $this->tplId = $this->createTplId($tplname);
        }
    }

    private function createTplId($tplname)
    {
        list($name, $type) = Resource::parseResourceName($tplname);
        if ($type !== 'file') {
            $name = md5($name);
        }
        $temp = $this->sdopx->getTemplateJoined() . $name;
        $temp = strtolower(str_replace(array('.', ':', ';', '|', '/', ' ', '\\'), '_', $temp));
        if (isset($temp[32])) {
            $temp = md5($temp);
        }
        return $type . '_' . $temp;
    }

    public function fetch($tplname)
    {
        $this->tplname = $tplname;
        $this->tplId = $this->createTplId($tplname);
        return $this->fetchTpl();
    }

    public function fetchTpl()
    {
        if ($this->tplId == null) {
            return;
        }
        //如果强制编译
        if ($this->sdopx->compile_force) {
            return $this->compileTemplate();
        }
        return $this->runTemplate();
    }

    public function addNamespace($name)
    {
        $this->namespace[md5($name)] = $name;
    }

    public function getSource()
    {
        if ($this->source === null) {
            $this->source = Resource::getTplSource($this);
        }
        return $this->source;
    }

    public function getCompiler()
    {
        if ($this->compiler === null) {
            $this->compiler = new Compiler($this->sdopx, $this);
        }
        return $this->compiler;
    }

    public function createChildTemplate($tpl_name)
    {
        return new Template($tpl_name, $this->sdopx, $this);
    }

    public function compileTemplateSource()
    {
        $source = $this->getSource();
        $source->load();
        $this->addDependency($source);
        return $this->getCompiler()->compileTemplate();
    }

    public function compileTemplate()
    {
        $code = $this->compileTemplateSource();
        $runCode = $this->writeCachedContent($code);
        return $runCode;
    }

    private function writeCachedContent($content)
    {
        $this->property['version'] = Sdopx::VERSION;
        $this->property['codeid'] = $this->tplId;
        $_cache = [];
        $content = $this->createTemplateCodeFrame($content);
        try {
            @eval('?>' . $content);
            if (isset($_cache['property']) && isset($_cache['unifunc'])) {
                Template::$complie_cache[$this->tplId] = $_cache;
            }
        } catch (\Exception $err) {
            $this->sdopx->rethrow($err);
        }
        $file = Utils::path($this->sdopx->runtime_dir, $this->tplId . '.php');
        file_put_contents($file, $content . ' return $_cache;');
        if (isset($_cache['unifunc']) && is_callable($_cache['unifunc'])) {
            return $this->run($_cache['unifunc']);
        }
        return '';
    }

    private function createTemplateCodeFrame($content)
    {
        $output = [];
        $output[] = '<?php';
        if (count($this->namespace) > 0) {
            foreach ($this->namespace as $names) {
                $output[] = 'use ' . $names . ';';
            }
        }
        $output[] = 'if(!isset($_cache)){ $_cache=[]; }';
        $output[] = '$_cache[\'property\'] = ' . var_export($this->property, true) . ';';
        $output[] = '$_cache[\'unifunc\']=function($_sdopx,$__out){';
        $output[] = 'try{';
        $output[] = $content;
        $output[] = '} catch (\ErrorException $exception) { $__out->throw($exception);}';
        $output[] = '};';
        return join("\n", $output);

    }

    public function addDependency(Source $source)
    {
        if ($this->parent !== null) {
            $this->parent->addDependency($source);
        }
        $tplId = $source->tplId;
        $this->property['dependency'][$tplId] = [
            'name' => $source->tplname,
            'time' => $source->timestamp,
            'type' => $source->type
        ];
    }

    //验证模板是否有效
    public function validProperties($property)
    {
        $this->property['version'] = (isset($property['version'])) ? $property['version'] : '';
        if ($this->property['version'] !== Sdopx::VERSION) {
            return false;
        }
        if (isset($property['dependency'])) {
            if (!isset($this->property['dependency'])) {
                $this->property['dependency'] = [];
            }
            $this->property['dependency'] = array_merge($this->property['dependency'], $property['dependency']);
        }
        if (isset($this->property['dependency'])) {
            foreach ($this->property['dependency'] as $item) {
                $type = $item['type'];
                $tpl_name = $item['name'];
                $instance = Resource::getResource($type);
                $mtime = $instance->getTimestamp($tpl_name, $this->sdopx);
                if ($mtime == 0 || ($mtime >= 0 && $mtime > $item['time'])) {
                    return false;
                }
            }
        }
        return true;
    }

    private function run($unifunc)
    {
        $__out = new Outer($this->sdopx);
        $_sdopx = $this->sdopx;
        try {
            call_user_func($unifunc, $_sdopx, $__out);
        } catch (\ErrorException $exception) {
            $__out->throw($exception);
        }
        return $__out->getCode();
    }

    private function runTemplate()
    {
        $file = Utils::path($this->sdopx->runtime_dir, $this->tplId . '.php');
        if (!isset(Template::$complie_cache[$this->tplId])) {
            if (file_exists($file)) {
                Template::$complie_cache[$this->tplId] = require($file);
            }
        }
        if (isset(Template::$complie_cache[$this->tplId])) {
            $_cache = Template::$complie_cache[$this->tplId];
            if ($this->validProperties($_cache['property'])) {
                return $this->run($_cache['unifunc']);
            } else {
                if (isset(Template::$complie_cache[$this->tplId])) {
                    unset(Template::$complie_cache[$this->tplId]);
                }
            }
        }
        return $this->compileTemplate();
    }

    public function getSubTemplate($tplname, $params = [])
    {
        $temp = [];
        foreach ($params as $key => $val) {
            if (isset($this->sdopx->_book[$key])) {
                $temp[$key] = $this->sdopx->_book[$key];
            }
            $this->sdopx->_book[$key] = $val;
        }
        try {
            $tpl = $this->createChildTemplate($tplname);
            $code = $tpl->fetchTpl();
            foreach ($params as $key => $val) {
                if (isset($temp[$key])) {
                    $this->sdopx->_book[$key] = $temp[$key];
                } else {
                    unset($this->sdopx->_book[$key]);
                }
            }
            return $code;
        } catch (\Exception $exception) {
            throw new \ErrorException($exception->getMessage());
        }
    }
}
