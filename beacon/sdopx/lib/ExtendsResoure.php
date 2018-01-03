<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/7/17
 * Time: 15:32
 */

namespace sdopx\lib;


use sdopx\Sdopx;

class ExtendsResoure implements BaseResource
{

    /**
     * @param $tplname
     * @param Sdopx $sdopx
     * @return array
     */
    public function fetch($tplname, Sdopx $sdopx)
    {
        $names = explode('|', $tplname);
        if (count($names) < 2) {
            $sdopx->rethrow("Sdopx 解析母版继承错误{$tplname} .");
        }
        $tplchild = array_pop($names);
        $extends = join('|', $names);
        list($name, $type) = Resource::parseResourceName($tplchild);
        $instance = Resource::getResource($type);
        $temp = $instance->fetch($tplchild, $sdopx);
        $temp['content'] = $sdopx->left_delimiter . 'extends file=\'' . $extends . '\'' . $sdopx->right_delimiter . $temp['content'];
        return $temp;
    }

    /**
     * @param $tplname
     * @param Sdopx $sdopx
     * @return int
     */
    public function getTimestamp($tplname, Sdopx $sdopx)
    {
        $names = explode('|', $tplname);
        if (count($names) < 2) {
            $sdopx->rethrow("Sdopx 解析母版继承错误{$tplname} .");
        }
        $tplchild = array_pop($names);
        $extends = join('|', $names);
        list($name, $type) = Resource::parseResourceName($tplchild);
        $instance = Resource::getResource($type);
        $filemtime = $instance->getTimestamp($tplchild, $sdopx);
        return $filemtime;
    }
}