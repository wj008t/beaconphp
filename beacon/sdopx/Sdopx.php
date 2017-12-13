<?php


namespace sdopx {

    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    if (!defined('SDOPX_DIR')) {
        define('SDOPX_DIR', __DIR__ . DS);
    }

    if (!defined('ROOT_DIR')) {
        define('ROOT_DIR', $_SERVER["DOCUMENT_ROOT"] . DS);
    }
    //注册自动加载类
    spl_autoload_register(function ($class) {
        $names = explode('\\', ltrim($class, '\\'));
        if (count($names) < 2 && $names[0] != 'sdopx') {
            return;
        }
        array_shift($names);
        if (preg_match('@^[a-z]+://@i', SDOPX_DIR)) {
            $file_name = str_replace(['\\', '/'], '/', SDOPX_DIR . join('\\', $names) . '.class.php');
        } else {
            $file_name = str_replace(['\\', '/'], DS, SDOPX_DIR . join('\\', $names) . '.class.php');
        }
        if (file_exists($file_name)) {
            require $file_name;
        }
        return false;
    });

    use \sdopx\libs\{
        Utils, Template
    };

    class Sdopx extends Template
    {

        const SDOPX_VERSION = 'Sdopx-1.3.0';

        /**
         * 过滤器枚举
         */
        const FILTER_POST = 'post';
        const FILTER_PRE = 'pre';
        const FILTER_OUTPUT = 'output';

        //<editor-fold defaultstate="collapsed" desc="类属性">
        /**
         * 模板目录
         * @var array
         */
        private $template_dir = [];

        /**
         * 编译文件目录
         *
         * @var string
         */
        private $compile_dir = null;

        /**
         * 插件目录
         * @var array
         */
        private $plugins_dir = [];

        /**
         * 是否强制编译
         *
         * @var boolean
         */
        public $force_compile = false;

        /**
         * 调试模式
         * @var boolean
         */
        public $compile_save = true;

        /**
         * 是否开启编译检查
         *
         * @var boolean
         */
        public $compile_check = true;
        public $compile_format = 1;

        /**
         * 编译ID
         * @var string
         */
        public $compile_id = null;

        /**
         * 模板分解符开始
         *
         * @var string
         */
        public $left_delimiter = '{';

        /**
         * 模板分解符结束
         *
         * @var string
         */
        public $right_delimiter = '}';

        /**
         * 模板变量集合
         * @var array
         */
        public $_book = [];
        /**
         * 配置变量集合
         * @var array
         */
        public $_config_vars = [];
        // public static $cache_resource_strings = [];

        /**
         * 过滤器
         * @var array
         */
        public static $regfilters = [];
        public static $functions = [];

        //所指向的控制器
        public $controller = null;

        //</editor-fold>

        /**
         * 初始化一个 模板
         */
        public function __construct()
        {
            $this->setTemplateDir(Utils::path(ROOT_DIR, 'view'));
            $this->setCompileDir(Utils::path(ROOT_DIR, 'runtime'));
            $this->setPluginsDir(Utils::path(SDOPX_DIR, 'plugins'));
            $this->sdopx = $this;
        }

        public function setting($key, $value)
        {
            $key = strtolower($key);
            if (in_array($key, ['force_compile', 'compile_save', 'compile_check', 'compile_format', 'left_delimiter', 'right_delimiter'])) {
                $this->$key = $value;
            }
        }

        public function assign($key, $val = null)
        {
            if (is_array($key)) {
                $this->_book = array_replace($this->_book, $key);
            } else {
                $this->_book[$key] = $val;
            }
        }

        public function fetch($tplname)
        {
            if (!isset($this->_book['sdopx'])) {
                $this->_book['sdopx']['get'] = $_GET;
                $this->_book['sdopx']['post'] = $_POST;
                $this->_book['sdopx']['request'] = $_REQUEST;
                if (session_status() == PHP_SESSION_ACTIVE) {
                    $this->_book['sdopx']['session'] = $_SESSION;
                }
                $this->_book['sdopx']['cookie'] = $_COOKIE;
                $this->_book['sdopx']['server'] = $_SERVER;
                $this->_book['sdopx']['config'] = $this->_config_vars;
            }
            if ($tplname != $this->tplname) {
                $this->tplname = $tplname;
                $this->tplId = $this->createTplId($this->tplname);
            }
            return $this->fetchTpl(null);
        }


        public function display($tplname)
        {
            echo $this->fetch($tplname);
        }

        /**
         * 设置模板目录
         * @param string $template_dir
         * @return \sdopx\Sdopx
         */
        public function setTemplateDir($template_dir)
        {
            $this->template_dir = [];
            foreach ((array)$template_dir as $k => $v) {
                $this->template_dir[$k] = Utils::path($v);
            }
            return $this;
        }

        /**
         * 添加 模板目录
         * @param type $template_dir
         * @param type $key
         * @return \sdopx\Sdopx
         */
        public function addTemplateDir($template_dir, $key = null)
        {
            $this->template_dir = (array)$this->template_dir;
            if (is_array($template_dir)) {
                foreach ($template_dir as $k => $v) {
                    $v = Utils::path($v);
                    if (is_int($k)) {
                        $this->template_dir[] = $v;
                    } else {
                        $this->template_dir[$k] = $v;
                    }
                }
            } else {
                $v = Utils::path($template_dir);
                if ($key) {
                    $this->template_dir[$key] = $v;
                } else {
                    $this->template_dir[] = $v;
                }
            }
            return $this;
        }

        /**
         * 获取模板目录
         * @param mixd $index
         * @return array
         */
        public function getTemplateDir($key = null)
        {
            if ($key != null) {
                return isset($this->template_dir[$key]) ? $this->template_dir[$key] : NULL;
            }
            return (array)$this->template_dir;
        }

        /**
         * 设置插件目录
         * @param string|array $plugins_dir
         * @return \sdopx\Sdopx
         */
        public function setPluginsDir($plugins_dir)
        {
            $this->plugins_dir = array();
            foreach ((array)$plugins_dir as $k => $v) {
                $this->plugins_dir[$k] = Utils::path($v);
            }
            return $this;
        }

        public function loadPlugin($name, $inc = false)
        {
            static $files = [];
            if (!empty($files[$name])) {
                if ($inc) {
                    require $files[$name];
                }
                return $files[$name];
            }
            $plugin = $this->getPluginsDir();
            foreach ($plugin as $path) {
                $file_name = Utils::path($path, $name . '.php');
                if (file_exists($file_name)) {
                    $files[$name] = $file_name;
                    if ($inc) {
                        require $file_name;
                    }
                    if (preg_match('@^phar://@i', $file_name)) {
                        return $file_name;
                    }
                    return $files[$name] = realpath($file_name);
                }
            }
            return false;
        }

        /**
         * 添加插件目录
         * @param string|array $plugins_dir
         * @return \sdopx\Sdopx
         */
        public function addPluginsDir($plugins_dir)
        {
            $this->plugins_dir = (array)$this->plugins_dir;
            if (is_array($plugins_dir)) {
                foreach ($plugins_dir as $k => $v) {
                    if (is_int($k)) {
                        $this->plugins_dir[] = Utils::path($v);
                    } else {
                        $this->plugins_dir[$k] = Utils::path($v);
                    }
                }
            } else {
                $this->plugins_dir[] = Utils::path($plugins_dir);
            }
            $this->plugins_dir = array_unique($this->plugins_dir);
            return $this;
        }

        /**
         * 获取插件目录
         * @return array
         */
        public function getPluginsDir()
        {
            return (array)$this->plugins_dir;
        }

        /**
         * 设置编译目录
         * @param string $compile_dir
         * @return \sdopx\Sdopx
         */
        public function setCompileDir($compile_dir)
        {
            $this->compile_dir = Utils::path($compile_dir);
            return $this;
        }

        /**
         * 获得编译目录
         * @return string path to compiled templates
         */
        public function getCompileDir()
        {
            if (!is_dir($this->compile_dir)) {
                Utils::makeDir($this->compile_dir);
            }
            return Utils::path(realpath($this->compile_dir));
        }

        /**
         * 获得模板ID
         * @param type $tplname
         * @param type $cache_id
         * @param type $compile_id
         * @return type
         */
        public function getTemplateJoined()
        {
            return join(';', $this->template_dir);
        }

        public function registerResource($type, $sourceobj)
        {
            \sdopx\libs\Resource::$resources[$type] = $sourceobj;
        }

        public function registerFilter($type, $callback)
        {
            if (!isset(self::$regfilters[$type])) {
                self::$regfilters[$type] = [];
            }
            self::$regfilters[$type][] = $callback;
        }

        public function registerFunction($name, $funcname)
        {
            if (is_string($funcname)) {
                self::$functions[$name] = $funcname;
            }
        }

    }

    class SdopxException extends \Exception
    {

        public static $escape = false;

        public function __toString()
        {
            return ' --> Sdopx: ' . (self::$escape ? htmlentities($this->message) : $this->message) . ' <-- ';
        }

    }

    class SdopxCompilerException extends SdopxException
    {

        public function __toString()
        {
            return ' --> Sdopx Compiler: ' . $this->message . ' <-- ';
        }

        /**
         * The line number of the template error
         *
         * @type int|null
         */
        public $line = null;

        /**
         * The template source snippet relating to the error
         *
         * @type string|null
         */
        public $source = null;

        /**
         * The raw text of the error message
         *
         * @type string|null
         */
        public $desc = null;

        /**
         * The resource identifier or template name
         *
         * @type string|null
         */
        public $template = null;

    }

}
