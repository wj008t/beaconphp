<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 20:44
 */

namespace beacon;


class Request
{
    private static $instance = null;
    private $header = null;
    private $content_type = 'text/html; charset=utf-8';

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new Request();
        }
        return self::$instance;
    }

    public function get(string $name = null, $def = null)
    {
        return $this->req($_GET, $name, $def);
    }

    public function post(string $name = null, $def = null)
    {
        return $this->req($_POST, $name, $def);
    }

    public function param(string $name = null, $def = null)
    {
        return $this->req($_REQUEST, $name, $def);
    }

    public function getSession(string $name = null, $def = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($name)) {
            return $_SESSION;
        }
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $def;
    }

    public function setSession(string $name, $value)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$name] = $value;
    }

    public function delSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    public function getCookie(string $name, $def = null)
    {
        return isset($_COOKIE["name"]) ? $_COOKIE["name"] : $def;
    }

    public function setCookie(string $name, $value, $options)
    {
        if ($options == null) {
            return setcookie($name, $value);
        }
        if (is_integer($options)) {
            return setcookie($name, $value, $options);
        }
        $expire = isset($options['expire']) ? intval($options['expire']) : 0;
        $path = isset($options['path']) ? intval($options['path']) : '';
        $domain = isset($options['domain']) ? intval($options['domain']) : '';
        $secure = isset($options['secure']) ? intval($options['secure']) : false;
        $httponly = isset($options['httponly ']) ? intval($options['httponly ']) : false;
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);

    }

    public function req(array $data, string $name = null, $def = null)
    {
        if (empty($name)) {
            return $data;
        }
        $type = '';
        if (preg_match('@^(.*):([abfis])@', $name, $m)) {
            $type = $m[2];
            $name = $m[1];
        }
        switch ($type) {
            case 's':
                if (!isset($data[$name])) {
                    if (is_string($def)) {
                        return $def;
                    }
                    return '';
                }
                if (is_string($data[$name])) {
                    return $data[$name];
                }
                return strval($data[$name]);
            case 'b':
                if (!isset($data[$name])) {
                    if (is_bool($def)) {
                        return $def;
                    }
                    if (is_string($def)) {
                        return $def == '1' || $def == 'on' || $def == 'yes' || $def == 'true';
                    }
                    return false;
                }
                return $data[$name] == '1' || $data[$name] == 'on' || $data[$name] == 'yes' || $data[$name] == 'true';
            case 'f':
                if (!isset($data[$name]) || !is_numeric($data[$name])) {
                    if (is_double($def) || is_float($def)) {
                        return $def;
                    }
                    return 0;
                }
                return doubleval($data[$name]);
            case 'i':
                if (!isset($data[$name]) || !is_numeric($data[$name])) {
                    if (is_integer($def)) {
                        return $def;
                    }
                    return 0;
                }
                return intval($data[$name]);
            case 'a':
                if (!isset($data[$name])) {
                    if (is_array($def)) {
                        return $def;
                    }
                    if ($def === null || $def === '') {
                        return [];
                    }
                    return [$def];
                }
                if (is_array($data[$name])) {
                    return $data[$name];
                }
                if ($data[$name] === null || $data[$name] === '') {
                    return [];
                }
                return [$data[$name]];
            default:
                return isset($data[$name]) ? $data[$name] : $def;
        }
    }

    public function file(string $name = null)
    {
        if (empty($name)) {
            return $_FILES;
        }
        return isset($_FILES[$name]) ? $_FILES[$name] : null;
    }

    public function route(string $name = null, $def = null)
    {
        $route = Route::get();
        if (empty($name)) {
            return $route;
        }
        if ($route == null) {
            return $def;
        }
        if (isset($route[$name])) {
            return $route[$name];
        }
        return $def;
    }

    public function getHeader(string $name = null)
    {
        if ($this->header == null) {
            $this->header = [];
            foreach ($_SERVER as $key => $value) {
                if ('HTTP_' == substr($key, 0, 5)) {
                    $key = strtolower(str_replace('_', '-', substr($key, 5)));
                    $this->header[$key] = $value;
                }
            }
        }
        if (empty($name)) {
            return $this->header;
        }
        $name = strtolower(str_replace('_', '-', $name));
        return isset($this->header[$name]) ? $this->header[$name] : '';
    }

    public function setHeader(string $name, string $value, bool $replace = true, $http_response_code = null)
    {
        $string = $name . ':' . $value;
        if ($replace) {
            if ($http_response_code == null) {
                header($string);
            } else {
                header($string, $replace, $http_response_code);
            }
        } else {
            if ($http_response_code == null) {
                header($string, false);
            } else {
                header($string, false, $http_response_code);
            }
        }
    }

    public function getIP(bool $proxy = false, bool $forward = false)
    {
        $ip = '';
        if ($proxy) {
            if ($forward) {
                $forwardIP = $this->getHeader('x-forwarded-for');
                if (!empty($forwardIP)) {
                    $temps = explode(',', $forwardIP);
                    foreach ($temps as $item) {
                        $item = trim($item);
                        if (filter_var($item, FILTER_VALIDATE_IP)) {
                            return $item;
                        }
                    }
                }
                $ip = $this->getHeader('x-real-ip');
            }
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        if (empty($ip)) {
            return '127.0.0.1';
        }
        return $ip;
    }

    public function getContentType($whole = false)
    {
        if ($whole) {
            return $this->content_type;
        }
        $temp = explode(';', $this->content_type);
        return $temp[0];
    }

    public function setContentType($type, $encoding = 'utf-8')
    {
        if (strpos($type, '/') === false) {
            $mime_types = [
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'text/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',
                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',
                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',
                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',
                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',
                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',
                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            ];
            $type = isset($mime_types[$type]) ? $mime_types[$type] : 'application/octet-stream';
        }
        $this->content_type = $type . '; charset=' . $encoding;
        $this->setHeader('Content-Type', $this->content_type);
    }

    public function config(string $name, $def = null)
    {
        return Config::get($name, $def);
    }

    public function isGet()
    {
        return $this->isMethod('get');
    }

    public function isMethod(string $method)
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == strtolower($method) ? true : false;
    }

    public function getMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function isPost()
    {
        return $this->isMethod('post');
    }

    public function isAjax()
    {
        if (isset($_SERVER['REQUEST_AJAX']) && $_SERVER['REQUEST_AJAX'] == true) {
            return true;
        }
        return strtolower($this->getHeader('x-requested-with')) === 'xmlhttprequest';
    }

    public function getReferrer()
    {
        $referer = Request::instance()->getHeader('referer');
        if (empty($referer)) {
            $referer = Request::instance()->getHeader('referrer');
        }
        return $referer;
    }

}