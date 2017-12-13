<?php

namespace sdopx\libs;

use \sdopx\Sdopx;

abstract class Resource {

    /**
     * cache for Smarty_Template_Source instances
     *
     * @var array
     */
    public static $sources = array();

    /**
     * cache for Smarty_Template_Compiled instances
     *
     * @var array
     */
    public static $compileds = array();

    /**
     * cache for Smarty_Resource instances
     *
     * @var array
     */
    public static $resources = array();

    public static function parseResourceName($tplname, $default_type, &$name, &$type) {
        $parts = explode(':', $tplname, 2);
        if (!isset($parts[1]) || !isset($parts[0][1])) {
            $type = $default_type;
            $name = $tplname;
        } else {
            $type = $parts[0];
            $name = $parts[1];
        }
    }

    /**
     * 获得资源
     * @param \sdopx\libs\Template $_tpl
     * @return \sdopx\libs\Source
     */
    public static function source(Template $_tpl = null, $sdopx = null, $tplname = null, $name = null, $type = null) {
        if ($_tpl !== null) {
            $sdopx = $_tpl->sdopx;
            $tplname = $_tpl->tplname;
        }
        if ($name == '' || $type == '') {
            self::parseResourceName($tplname, 'file', $name, $type);
        }
        if (empty($tplname)) {
            $tplname = $type . ':' . $name;
        }
        //解析类型

        $resource = self::load($sdopx, $type);
        $_cache_key = $sdopx->getTemplateJoined() . $tplname;
        if (isset($_cache_key[150])) {
            $_cache_key = sha1($_cache_key);
        }
        if (isset(self::$sources[$_cache_key])) {
            return self::$sources[$_cache_key];
        }
        //填充数据
        $source = new Source($resource, $sdopx, $tplname, $type, $name);
        self::$sources[$_cache_key] = $source;
        return $source;
    }

    /**
     * 加载资源器
     * @param \sdopx\Sdopx $sdopx
     * @param string $type
     * @return type
     * @throws SmartyException
     */
    public static function load(Sdopx $sdopx, $type) {
        if (isset(self::$resources[$type])) {
            return self::$resources[$type];
        } else {
            $_resource_class = '\\sdopx\\libs\\Resource' . ucfirst($type);
            if (class_exists($_resource_class)) {
                self::$resources[$type] = new $_resource_class();
                return self::$resources[$type];
            } else {
                throw new \sdopx\SdopxException('没有找到 ' . $type . ' 类型的源数据。');
            }
        }
    }

}
