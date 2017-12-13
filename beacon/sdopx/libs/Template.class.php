<?php

namespace sdopx\libs;

use sdopx\Sdopx;

/**
 * @property \sdopx\libs\Compile $compiler
 * @property \sdopx\libs\Source $source
 */
class Template
{

    //缓存编译后的代码
    public static $compile_code = [];

    /**
     * @var Sdopx
     */
    public $sdopx = null;

    /**
     * 模板ID
     */
    public $tplId = null;

    /**
     * 父模板
     * @var Template
     */
    public $parent = null;

    /**
     * 模板字符串名称
     * @var  string
     */
    public $tplname = null;

    /**
     * 模板依赖项
     * @var array
     */
    public $property = [
        'dependency' => []
    ];
    //继承模板
    public $extends_uid = [];
    public $recompile = false;
    //要用到的文件
    public $func_files = [];
    //要用到的文件
    public $class_files = [];

    /**
     * 创建模板
     * @param type $tplname
     * @param \sdopx\Sdopx $sdopx
     * @param \sdopx\libs\Template $_parent
     */
    public function __construct($tplname, Sdopx $sdopx = null, Template $_parent = null)
    {
        $this->tplname = $tplname;
        $this->sdopx = $sdopx;
        $this->parent = $_parent;
        $this->tplId = $this->createTplId($this->tplname);
    }

    public function createTplId($tplname)
    {
        Resource::parseResourceName($tplname, 'file', $name, $type);
        $file_name = md5($this->sdopx->getTemplateJoined() . $this->tplname)
            . '_' . $type . '_' . ltrim(str_replace(['.', '/', '\\', ':', '|'], '_', strtolower(basename($name))), '_');
        if (isset($file_name[50])) {
            $file_name = md5($file_name);
        }
        return $file_name;
    }

    //输出内容
    public function fetchTpl($tpl = null)
    {
        if ($tpl == null) {
            $tpl = $this;
        }
        if (is_string($tpl)) {
            $tpl = $this->createChildTemplate($tpl);
        }
        $include_file = Utils::path($this->sdopx->getCompileDir(), $tpl->tplId . '.php');
        if ($this->sdopx->force_compile) {
            return $tpl->compileTemplate($include_file);
        }
        if ($this->sdopx->compile_check == false) {
            return $tpl->showTemplate($include_file);
        }
        if (is_file($include_file)) {
            return $tpl->showTemplate($include_file);
        }
        return $tpl->compileTemplate($include_file);
    }

    //显示模板
    public function showTemplate($include_file)
    {
        $_valid = false;
        @include $include_file;
        if (!$_valid) {
            return $this->compileTemplate($include_file);
        } else {
            try {
                ob_start();
                call_user_func($this->property['unifunc'], $this->sdopx);
                $_output = ob_get_clean();
            } catch (Exception $e) {
                ob_get_clean();
                throw $e;
            }
            if (isset(Sdopx::$regfilters['output'])) {
                foreach (Sdopx::$regfilters['output'] as $func) {
                    $_output = call_user_func($func, $_output, $this->sdopx);
                }
            }
            return $_output;
        }
    }

    //编译模板
    public function compileTemplate($include_file)
    {
        if (isset(self::$compile_code[$this->tplId])) {
            $outputphp = self::$compile_code[$this->tplId];
            $compile_save = false;
        } else {
            self::$compile_code[$this->tplId] = $outputphp = $this->compileTemplateSource();
            $compile_save = true;
        }
        if ($compile_save && $this->sdopx->compile_save) {
            $this->writeCachedContent($outputphp, $include_file);
        }
        try {
            ob_start();
            foreach ($this->func_files as $file => $func) {
                if (!function_exists($func)) {
                    @include_once($file);
                }
            }
            foreach ($this->class_files as $file => $func) {
                if (!class_exists($func)) {
                    @include_once($file);
                }
            }
            eval('$_sdopx=$this->sdopx;?' . '>' . $outputphp);
            $_output = ob_get_clean();
        } catch (Exception $e) {
            ob_get_clean();
            throw $e;
        }
        if (isset(Sdopx::$regfilters['output'])) {
            foreach (Sdopx::$regfilters['output'] as $func) {
                $_output = call_user_func($func, $_output, $this->sdopx);
            }
        }
        return $_output;
    }

    public function createChildTemplate($tplname)
    {
        $tpl = new Template($tplname, $this->sdopx, $this);
        return $tpl;
    }

    //写入缓存文件
    public function writeCachedContent($content, $includefile)
    {
        $this->property['version'] = Sdopx::SDOPX_VERSION;
        $this->property['unifunc'] = 'content_' . str_replace(array('.', ','), '_', uniqid('', true));
        $content = $this->createTemplateCodeFrame($content, true);
        if ($this->sdopx->compile_check) {
            eval('?>' . $content);
        }
        file_put_contents($includefile, $content);
        return true;
    }

    //创建代码片段
    public function createTemplateCodeFrame($content = '')
    {
        $code = "<?php if(!defined('SDOPX_DIR')) exit('no direct access allowed');\n";
        $code .= "\$_valid = \$this->validProperties(" . var_export($this->property, true) . ");\n";
        $code .= 'if ($_valid && !is_callable(\'' . $this->property['unifunc'] . '\')) {function ' . $this->property['unifunc'] . "(\$_sdopx) {\n";
        foreach ($this->func_files as $file => $func) {
            $code .= "if(!function_exists('{$func}')){ require('{$file}');}\n";
        }
        foreach ($this->class_files as $file => $func) {
            $code .= "if(!class_exists('{$func}')){ require('{$file}');}\n";
        }
        $code .= '?>';
        $code .= $content;
        $code .= "<?php }}";
        return $code;
    }

    /**
     * 创建资源
     */
    public function __get($name)
    {
        switch ($name) {
            case 'source':
                if (strlen($this->tplname) == 0) {
                    throw new Exception('Missing template name');
                }
                $this->source = Resource::source($this);
                return $this->source;
            case 'compiler':
                $this->compiler = new Compile;
                return $this->compiler;
        }
    }

    //添加依赖资源
    public function addDependency(Source $source)
    {
        if ($this->parent !== null) {
            $this->parent->addDependency($source);
        }
        $uid = $source->uid;
        $this->property['dependency'][$uid] = [
            'path' => $source->filepath,
            'time' => $source->timestamp,
            'type' => $source->type,
        ];
    }

    //编译文件
    public function compileTemplateSource()
    {
        if (!$this->source->isload) {
            $this->source->load();
        }
        $this->addDependency($this->source);
        $code = $this->compiler->compileTemplate($this);
        if ($this->parent != null) {
            foreach ($this->func_files as $filename => $callback) {
                $this->parent->func_files[$filename] = $callback;
            }
            foreach ($this->class_files as $filename => $callback) {
                $this->parent->class_files[$filename] = $callback;
            }
        }
        unset($this->compiler);
        if (isset(Sdopx::$regfilters['post'])) {
            foreach (Sdopx::$regfilters['post'] as $func) {
                $code = call_user_func($func, $code, $this->sdopx);
            }
        }
        return $code;
    }

    public function getSubTemplate($tplname, $params = [])
    {
        $temp = [];
        foreach ($params as $key => $val) {
            if (key_exists($key, $this->sdopx->_book)) {
                $temp[$key] = $this->sdopx->_book[$key];
            }
            $this->sdopx->_book[$key] = $val;
        }
        $tpl = $this->createChildTemplate($tplname);
        $code = $this->fetchTpl($tpl);
        foreach ($params as $key => $val) {
            if (key_exists($key, $temp)) {
                $this->sdopx->_book[$key] = $temp[$key];
            } else {
                unset($this->sdopx->_book[$key]);
            }
        }
        return $code;
    }

    //验证特征
    public function validProperties($property)
    {
        if (isset($property['dependency'])) {
            $this->property['dependency'] = array_merge($this->property['dependency'], $property['dependency']);
        }
        $this->property['version'] = (isset($property['version'])) ? $property['version'] : '';
        $this->property['unifunc'] = (isset($property['unifunc'])) ? $property['unifunc'] : '';
        if ($this->property['version'] != Sdopx::SDOPX_VERSION) {
            return false;
        }
        if ($this->sdopx->compile_check == false) {
            return true;
        }
        if ($this->sdopx->force_compile) {
            return false;
        }
        //验证依赖资源是否OK
        foreach ($this->property['dependency'] as $file) {
            if ($file['type'] == 'file') {
                if ($this->source->filepath == $file['path'] && isset($this->source->timestamp)) {
                    $mtime = $this->source->timestamp;
                } else {
                    $mtime = @filemtime($file['path']);
                }
            } else {
                $source = Resource::source(null, $this->sdopx, null, $file['path'], $file['type']);
                $mtime = $source->timestamp;
            }
            if (!$mtime || $mtime > $file['time']) {
                return false;
            }
        }
        return true;
    }

}
