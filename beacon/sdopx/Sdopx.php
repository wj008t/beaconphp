<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/7/17
 * Time: 9:33
 */
declare(strict_types=1);


namespace sdopx;

set_error_handler(function ($severity, $message, $filename, $lineno) {
    if (error_reporting() == 0) {
        return FALSE;
    }
    if (error_reporting() & $severity) {
        throw new \ErrorException($message, 0, $severity, $filename, $lineno);
    }
    return true;
});


/**
 * DS 换行符
 */

use sdopx\lib\Compiler;
use sdopx\lib\Utils;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
/**
 * SDOPX 模板引擎目录
 */
if (!defined('SDOPX_DIR')) {
    define('SDOPX_DIR', __DIR__ . DS);
}

require_once("lib/Utils.php");
require_once("lib/Template.php");

/**
 * 注册自动引入路径
 */
class SdopxException extends \Exception
{

    protected $stack = '';

    public function setFile(string $file)
    {
        $this->file = $file;
    }

    public function setLine(int $line)
    {
        $this->line = $line;
    }

    public function setStack(string $stack)
    {
        $this->stack = $stack;
    }

    public function getStack()
    {
        return $this->stack;
    }

    public function __toString()
    {
        return $this->stack . "\n" . parent::__toString();
    }

}

class Sdopx extends \sdopx\lib\Template
{
    /**
     * 版本信息
     * @var string
     */
    const VERSION = '1.0.0';

    public static $debug = false;

    public static $extension = 'tpl';

    //注册的函数
    public static $functions = [];
    //注册的过滤器
    public static $filters = [];
    //注册的资源类型
    public static $resources = [];

    public static $compiler_dirs = [];
    public static $plugin_dirs = [];
    //上下文
    public $context = null;
    //运行文件目录
    public $runtime_dir = '';
    //强行编译
    public $compile_force = false;
    //编译检查
    public $compile_check = true;
    //左分界符
    public $left_delimiter = '{';
    //右分界符
    public $right_delimiter = '}';

    public $_book = [];
    public $_config = [];
    private $template_dirs = [];
    private $joined = '';

    //函数
    public $funcMap = [];

    public function __construct($context = null)
    {
        parent::__construct();
        $this->context = $context;
        if (defined(ROOT_DIR)) {
            $this->runtime_dir = Utils::path(ROOT_DIR, 'runtime');
        } else if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $this->runtime_dir = Utils::path($_SERVER['DOCUMENT_ROOT'], 'runtime');
        }
    }

    public function setting($key, $value)
    {
        $key = strtolower($key);
        if (in_array($key, ['compile_force', 'compile_check', 'left_delimiter', 'right_delimiter'])) {
            $this->$key = $value;
        }
    }

    public function setRuntimeDir($dir_names)
    {
        $this->runtime_dir = $dir_names;
    }

    public function setTemplateDir($dir_names)
    {
        $this->template_dirs = [];
        $this->joined = '';
        if (empty($dir_names)) {
            return $this;
        }
        if (gettype($dir_names) == 'string') {
            $this->template_dirs[] = $dir_names;
            return $this;
        }
        foreach ($dir_names as $key => $value) {
            if (gettype($value) != 'string' || empty($value)) {
                continue;
            }
            $this->template_dirs[$key] = $value;
        }
        return $this;
    }

    public function addTemplateDir(...$dir_names)
    {
        if (count($dir_names) == 0) {
            return $this;
        }
        foreach ($dir_names as $value) {
            if (gettype($value) != 'string' || empty($value)) {
                continue;
            }
            $this->template_dirs[] = $value;
        }
        return $this;
    }

    public function getTemplateDir($key = null)
    {
        if ($key === null) {
            return $this->template_dirs;
        }
        if (gettype($key) === 'string' || gettype($key) === 'integer') {
            return isset($this->template_dirs[$key]) ? $this->template_dirs[$key] : null;
        }
        return null;
    }

    public function getTemplateJoined()
    {
        if (!empty($this->joined)) {
            return $this->joined;
        }
        $temp = [];
        foreach ($this->template_dirs as $item) {
            $temp[] = $item;
        }
        $joined = join(";", $temp);
        if (isset($joined[32])) {
            $joined = md5($joined);
        }
        $this->joined = $joined;
        return $joined;
    }

    public function assign($key, $value = null)
    {
        if (gettype($key) == 'string') {
            $this->_book[$key] = $value;
            return $this;
        }
        foreach ($key as $k => $v) {
            $this->_book[$k] = $v;
        }
        return $this;
    }

    public function assignConfig($key, $value = null)
    {
        if (gettype($key) == 'string') {
            $this->_config[$key] = $value;
            return $this;
        }
        foreach ($key as $k => $v) {
            $this->_config[$k] = $v;
        }
        return $this;
    }

    //过滤器注册
    public static function registerFilter(string $type, $func, $file = null)
    {
        if (gettype($type) !== 'string') {
            return;
        }
        if (!isset(self::$filters[$type])) {
            self::$filters[$type] = [];
        }
        if (gettype($func) == 'string') {
            if (empty($file)) {
                return;
            }
        }
        array_push(self::$filters[$type], ['func' => $func, 'file' => $file]);
    }

    //注册函数
    public static function registerFunction($name, $func, $file = null)
    {
        if (gettype($name) !== 'string') {
            return;
        }
        if (gettype($func) == 'string') {
            if (empty($file)) {
                return;
            }
        }
        self::$functions[$name] = ['func' => $func, 'file' => $file];
    }

    //添加插件目录
    public static function addPluginDir($dirname)
    {
        if (is_array($dirname)) {
            foreach ($dirname as $item) {
                if (is_string($item)) {
                    $key = md5($item);
                    self::$plugin_dirs[$key] = $item;
                }
            }
        } elseif (is_string($dirname)) {
            $key = md5($dirname);
            self::$plugin_dirs[$key] = $dirname;
        }
    }

    //添加便宜器目录
    public static function addCompileDir($dirname)
    {
        if (is_array($dirname)) {
            foreach ($dirname as $item) {
                if (is_string($item)) {
                    $key = md5($item);
                    self::$compiler_dirs[$key] = $item;
                }
            }
        } elseif (is_string($dirname)) {
            $key = md5($dirname);
            self::$compiler_dirs[$key] = $dirname;
        }
    }

    public function rethrow($err, int $lineno = null, string $tplname = null)
    {
        if (is_string($err)) {
            $err = new SdopxException($err);
        } elseif ($err instanceof \Exception) {
            $err = new SdopxException($err->getMessage());
        }
        if (!Sdopx::$debug) {
            throw $err;
        }
        if ($lineno == null || $tplname == null) {
            throw $err;
        }
        list($name, $type) = \sdopx\lib\Resource::parseResourceName($tplname);
        $instance = \sdopx\lib\Resource::getResource($type);
        $err->setLine($lineno);
        if (!$instance) {
            $err->setFile($tplname);
            $err->setStack($tplname . ':' . $lineno . $err->getStack());
        }
        $temp = $instance->fetch($tplname, $this);
        $content = $temp['content'];
        $lines = explode("\n", $content);
        $len = count($lines);
        $start = ($lineno - 3) < 0 ? 0 : $lineno - 3;
        $end = ($lineno + 3) >= $len ? $len - 1 : $lineno + 3;
        $lines = array_slice($lines, $start, $end - $start, true);
        foreach ($lines as $idx => &$line) {
            $curr = $idx + 1;
            $line = ($curr == $lineno ? ' >> ' : '    ') . $curr . '| ' . $line;
        }
        $context = join("\n", $lines);
        $err->setFile($name);
        $err->setStack($tplname . ':' . $lineno . "\n" . $context . "\n" . $err->getStack());
        throw $err;
    }

    public static function autoload()
    {
        spl_autoload_register(function ($class) {
            //var_export($class);
            //echo "\n";
            //编译器
            if (preg_match('@^sdopx\\\\compile\\\\(.+)$@', $class, $m)) {
                foreach (self::$compiler_dirs as $dirname) {
                    $path = Utils::path($dirname, "{$m[1]}.php");
                    if (file_exists($path)) {
                        @include($path);
                        return;
                    }
                }
            }
            if (preg_match('@^sdopx\\\\plugin\\\\(.+)$@', $class, $m)) {
                foreach (self::$plugin_dirs as $dirname) {
                    $path = Utils::path($dirname, "{$m[1]}.php");
                    if (file_exists($path)) {
                        @include($path);
                        return;
                    }
                }
            }
            if (preg_match('@^sdopx\\\\.+$@', $class, $mc)) {
                $path = Utils::path(SDOPX_DIR, "../{$class}.php");
                if (file_exists($path)) {
                    @include($path);
                    return;
                }
            }
        });
    }
}

Sdopx::autoload();