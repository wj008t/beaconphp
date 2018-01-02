<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/7/17
 * Time: 14:47
 */

namespace sdopx\lib;


use sdopx\Sdopx;

interface BaseResource
{
    public function fetch($tplname,Sdopx $sdopx);
    public function getTimestamp($tplname,Sdopx $sdopx);
}