<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/7/17
 * Time: 15:32
 */

namespace sdopx\lib;


use sdopx\Sdopx;

class FileResoure implements BaseResource
{

    /**
     * @param $tplname
     * @param Sdopx $sdopx
     * @return array
     */
    public function fetch($tplname, Sdopx $sdopx)
    {
        $filepath=Resource::getPath($tplname, $sdopx);
        if ($filepath == null) {
            return ['content'=>'', 'timestamp'=>0, 'filepath'=>$tplname];
        }
        $filemtime=filemtime($filepath);
        if ($filemtime === FALSE) {
            $filemtime=0;
        }
        $content=file_get_contents($filepath);
        return ['content'=>$content, 'timestamp'=>$filemtime, 'filepath'=>$filepath];
    }

    /**
     * @param $tplname
     * @param Sdopx $sdopx
     * @return int
     */
    public function getTimestamp($tplname, Sdopx $sdopx)
    {
        $filepath=Resource::getPath($tplname, $sdopx);
        if ($filepath == null) {
            return 0;
        }
        $filemtime=filemtime($filepath);
        if ($filemtime === FALSE) {
            $filemtime=0;
        }
        return $filemtime;
    }
}