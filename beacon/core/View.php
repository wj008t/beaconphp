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
    public $context = null;

    public function __construct(HttpContext $context)
    {
        $this->context = $context;
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
        $this->engine = new \sdopx\Sdopx($this->context);
        $template_dir = Utils::path(ROOT_DIR, Config::get('sdopx.template_dir', 'view'));
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
                     'sdopx.compile_force',
                     'sdopx.compile_check',
                     'sdopx.runtime_dir',
                     'sdopx.left_delimiter',
                     'sdopx.right_delimiter'
                 ] as $key) {
            $val = Config::get($key);
            if (!empty($val)) {
                $this->engine->setting($key, $val);
            }
        }
    }

    public function display($tplname)
    {
        $this->initialize();
        $this->engine->_book = $this->_book;
        return $this->engine->display($tplname);
    }

    public function fetch($tplname)
    {
        $this->initialize();
        $this->engine->_book = $this->_book;
        return $this->engine->fetch($tplname);
    }
}