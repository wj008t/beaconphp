<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/12
 * Time: 15:50
 */

namespace beacon;


class View
{

    private $_book = [];
    private $_config_vars = [];

    /**
     * @var \sdopx\Sdopx
     */
    public $engine = null;

    private static $instance = null;

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new View();
        }
        return self::$instance;
    }

    public function assign($key, $val = null)
    {
        if (is_array($key)) {
            $this->_book = array_replace($this->_book, $key);
        } else {
            $this->_book[$key] = $val;
        }
    }

    public function assignConfig($key, $val = null)
    {
        if (is_array($key)) {
            $this->_config_vars = array_replace($this->_config_vars, $key);
        } else {
            $this->_config_vars[$key] = $val;
        }
    }

    public function initialize()
    {
        if ($this->engine != null) {
            return;
        }
        if (Config::get('sdopx.debug')) {
            \sdopx\Sdopx::$debug = true;
        }
        $this->engine = new \sdopx\Sdopx();

        $template_dir = Config::get('sdopx.template_dir', 'view');
        if (is_array($template_dir)) {
            foreach ($template_dir as &$dir) {
                $dir = Utils::path(ROOT_DIR, $dir);
            }
        } else {
            $template_dir = Utils::path(ROOT_DIR, $template_dir);
        }
        $common_dir = Utils::path(ROOT_DIR, Config::get('sdopx.common_dir', 'view/common'));
        $runtime_dir = Utils::path(ROOT_DIR, Config::get('sdopx.runtime_dir', 'runtime'));
        $this->engine->setTemplateDir($template_dir);
        $this->engine->addTemplateDir($common_dir, 'common');
        $this->engine->setRuntimeDir($runtime_dir);
        $plugins_dir = Config::get('sdopx.plugin_dir');
        if (!empty($plugins_dir)) {
            if (is_array($plugins_dir)) {
                foreach ($plugins_dir as &$item) {
                    $item = Utils::path(ROOT_DIR, $item);
                }
            } elseif (is_string($plugins_dir)) {
                $plugins_dir = Utils::path(ROOT_DIR, $plugins_dir);
            }
            $this->engine->addPluginDir($plugins_dir);
        }
        foreach ([
                     'compile_force',
                     'compile_check',
                     'runtime_dir',
                     'left_delimiter',
                     'right_delimiter'
                 ] as $key) {
            $val = Config::get('sdopx.' . $key);
            if (!empty($val)) {
                $this->engine->setting($key, $val);
            }
        }
    }

    public function display($tplname)
    {
        $this->initialize();
        $this->engine->_book = $this->_book;
        $this->engine->_config = Config::get();
        echo $this->engine->fetch($tplname);
    }

    public function fetch($tplname)
    {
        $this->initialize();
        $this->engine->_book = $this->_book;
        return $this->engine->fetch($tplname);
    }
}