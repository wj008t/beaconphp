<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 2:02
 */

namespace beacon;


class Utils
{
    public static function path(...$paths)
    {
        $protocol = '';
        $path = trim(implode(DIRECTORY_SEPARATOR, $paths));
        if (preg_match('@^([a-z0-9]+://|/)(.*)@i', $path, $m)) {
            $protocol = $m[1];
            $path = $m[2];
        }
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
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
        if (DIRECTORY_SEPARATOR == '\\' && isset($protocol[4])) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $protocol . $path;
    }


    public static function toUnder($name)
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '_' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '_');
        return $name;
    }

    public static function toCamel($name)
    {
        $name = preg_replace('@_+@', '_', $name);
        $name = preg_replace_callback('@_[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, $name);
        $name = ucfirst($name);
        return $name;
    }

    public static function attrToCamel($name)
    {
        $name = preg_replace_callback('@-[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, trim($name, '-'));
        $name = lcfirst($name);
        return $name;
    }

    public static function camelToAttr($name)
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '-' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '-');
        return $name;
    }

    public static function randWord($len = 4)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $word = '';
        for ($i = 0; $i < $len; $i++) {
            $word .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $word;
    }

    public static function randNum($len = 4)
    {
        $chars = '0123456789';
        $word = '';
        for ($i = 0; $i < $len; $i++) {
            $word .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $word;
    }

    public static function isJsonString($str)
    {
        return is_string($str) && !empty($str) && preg_match('@^[\[\{].*[\]\}]$@', $str);
    }

    public static function makeDir($filedir, $mode = 0777)
    {
        if (!is_dir($filedir)) {
            $pfiledir = dirname($filedir);
            self::makeDir($pfiledir);
            @mkdir($filedir, $mode);
            @fclose(fopen($filedir . DIRECTORY_SEPARATOR . 'index.htm', 'w'));
        }
    }

}
