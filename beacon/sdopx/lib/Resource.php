<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/7/17
 * Time: 14:11
 */

namespace sdopx\lib;

use sdopx\Sdopx;

class Resource
{
    public static $resourceManager = [];

    /**
     * 提取类型
     * @param string $tplname
     * @return array
     */
    public static function parseResourceName(string $tplname)
    {
        if (preg_match('@^(\w+):@', $tplname, $match)) {
            $type = strtolower($match[1]);
            $name = preg_replace('@^(\w+):@', '', $tplname);
            return [$name, $type];
        }
        return [$tplname, 'file'];
    }

    /**
     * 注册资源
     * @param string $type
     * @param BaseResource $instance
     */
    public static function registerResource(string $type, BaseResource $instance)
    {
        if (gettype($type) !== 'string') {
            return;
        }
        if ($instance == null || !($instance instanceof BaseResource)) {
            return;
        }
        Resource::$resourceManager[$type] = $instance;
    }

    /**
     * 获取资源
     * @param string $type
     * @return BaseResource
     */
    public static function getResource(string $type): BaseResource
    {
        if (isset(Resource::$resourceManager[$type])) {
            return Resource::$resourceManager[$type];
        }
        return null;
    }

    /**
     * @param $sdopx
     * @param $tplname
     * @param $tplId
     * @return \sdopx\lib\Source
     */
    public static function getSource($sdopx, $tplname, $tplId)
    {
        list($name, $type) = Resource::parseResourceName($tplname);
        $instance = Resource::getResource($type);
        return new \sdopx\lib\Source($instance, $sdopx, $tplname, $tplId, $name, $type);
    }

    public static function getTplSource(Template $tpl)
    {
        return Resource::getSource($tpl->sdopx, $tpl->tplname, $tpl->tplId);
    }

    /**
     * 获取资源路径
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return null|string
     */
    public static function getPath(string $tplname, Sdopx $sdopx)
    {
        if ($tplname[0] == '@') {
            $dirName = $sdopx->getTemplateDir('common');
            if (empty($dirName)) {
                //TODO 抛出异常
                return null;
            }
            $tplname = substr($tplname, 1);
            $filePath = Utils::path($dirName, $tplname);
            if ($filePath != '' && file_exists($filePath)) {
                return $filePath;
            }
            return null;
        }
        $tplDirs = $sdopx->getTemplateDir();
        if ($tplDirs == null) {
            return null;
        }
        foreach ($tplDirs as $key => $dirName) {
            if ($key === 'common') {
                continue;
            }
            $filePath = Utils::path($dirName, $tplname);
            if ($filePath != '' && file_exists($filePath)) {
                return $filePath;
            }
        }
        return null;
    }

}

Resource::registerResource('file', new FileResoure());