<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/2
 * Time: 21:40
 */

namespace sdopx\plugin;


use sdopx\lib\Outer;
use sdopx\Sdopx;

class HelloPlugin
{
    public static function block($param, $func, Outer $out, Sdopx $sdopx)
    {
        $name = (empty($params['name'])) ? '' : $params['name'];
        $branch = (empty($params['branch'])) ? '' : $params['branch'];
        $task = (empty($params['task'])) ? '' : $params['task'];
        $token = \app\flow\lib\Flow::getToken($task, $name, $branch);
        if ($token <= 0) {
            return;
        }
        $func();
    }
}