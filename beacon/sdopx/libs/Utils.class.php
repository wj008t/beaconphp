<?php

namespace sdopx\libs {

    use \sdopx\Sdopx;
    use \sdopx\SdopxException;

    class Utils
    {

        public static function path(...$paths)
        {
            $protocol = '';
            $path = trim(implode(DIRECTORY_SEPARATOR, $paths));

            if (preg_match('@^([a-z0-9]+://|/)(.*)@i', $path, $m)) {
                $protocol = strtolower($m[1]);
                $path = $m[2];
            }

            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
            $absolutes = [];
            foreach ($parts as $part) {
                if ('.' == $part) {
                    continue;
                }
                if ('..' == $part) {
                    array_pop($absolutes);
                } else {
                    $absolutes[] = $part;
                }
            }
            $path = implode(DIRECTORY_SEPARATOR, $absolutes);
            if (DIRECTORY_SEPARATOR == '\\' && !empty($protocol)) {
                $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
            }
            return $protocol . $path;
        }

        /**
         * 创建文件夹
         * @param $dirname
         * @throws \Exception
         */
        public static function makeDir($dirname, $mode = 0777)
        {
            if (empty($dirname)) {
                throw new \Exception("不存在的目录！");
            }
            if (!is_dir($dirname)) {
                $pfiledir = dirname($dirname);
                self::makeDir($pfiledir);
                @mkdir($dirname, $mode);
                @fclose(fopen($dirname . DIRECTORY_SEPARATOR . 'index.htm', 'w'));
                @unlink($dirname . DIRECTORY_SEPARATOR . 'index.htm');
            }
        }


    }

}