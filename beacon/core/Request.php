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
        if (empty($name)) {
            return $_GET;
        }
        $type = '';
        if (preg_match('@^(.*):([abfis])@', $name, $m)) {
            $type = $m[2];
            $name = $m[1];
        }
        switch ($type) {
            case 's':
                if (!isset($_GET[$name])) {
                    if (is_string($def)) {
                        return $def;
                    }
                    return '';
                }
                if (is_string($_GET[$name])) {
                    return $_GET[$name];
                }
                return strval($_GET[$name]);
            case 'b':
                if (!isset($_GET[$name])) {
                    if (is_bool($def)) {
                        return $def;
                    }
                    if (is_string($def)) {
                        return $def == '1' || $def == 'on' || $def == 'yes' || $def == 'true';
                    }
                    return false;
                }
                return $_GET[$name] == '1' || $_GET[$name] == 'on' || $_GET[$name] == 'yes' || $_GET[$name] == 'true';
            case 'f':
                if (!isset($_GET[$name]) || !is_numeric($_GET[$name])) {
                    if (is_double($def) || is_float($def)) {
                        return $def;
                    }
                    return 0;
                }
                return doubleval($_GET[$name]);
            case 'i':
                if (!isset($_GET[$name]) || !is_numeric($_GET[$name])) {
                    if (is_integer($def)) {
                        return $def;
                    }
                    return 0;
                }
                return intval($_GET[$name]);
            case 'a':
                if (!isset($_GET[$name])) {
                    if (is_array($def)) {
                        return $def;
                    }
                    return [$def];
                }
                if (is_array($_GET[$name])) {
                    return $_GET[$name];
                }
                return [$_GET[$name]];
            default:
                return isset($_GET[$name]) ? $_GET[$name] : $def;
        }
    }

    public function post(string $name = null, $def = null)
    {
        if (empty($name)) {
            return $_POST;
        }
        $type = '';
        if (preg_match('@^(.*):([abfis])@', $name, $m)) {
            $type = $m[2];
            $name = $m[1];
        }
        switch ($type) {
            case 's':
                if (!isset($_POST[$name])) {
                    if (is_string($def)) {
                        return $def;
                    }
                    return '';
                }
                if (is_string($_POST[$name])) {
                    return $_POST[$name];
                }
                return strval($_POST[$name]);
            case 'b':
                if (!isset($_POST[$name])) {
                    if (is_bool($def)) {
                        return $def;
                    }
                    if (is_string($def)) {
                        return $def == '1' || $def == 'on' || $def == 'yes' || $def == 'true';
                    }
                    return false;
                }
                return $_POST[$name] == '1' || $_POST[$name] == 'on' || $_POST[$name] == 'yes' || $_POST[$name] == 'true';
            case 'f':
                if (!isset($_POST[$name]) || !is_numeric($_POST[$name])) {
                    if (is_double($def) || is_float($def)) {
                        return $def;
                    }
                    return 0;
                }
                return doubleval($_POST[$name]);
            case 'i':
                if (!isset($_POST[$name]) || !is_numeric($_POST[$name])) {
                    if (is_integer($def)) {
                        return $def;
                    }
                    return 0;
                }
                return intval($_POST[$name]);
            case 'a':
                if (!isset($_POST[$name])) {
                    if (is_array($def)) {
                        return $def;
                    }
                    return [$def];
                }
                if (is_array($_POST[$name])) {
                    return $_POST[$name];
                }
                return [$_POST[$name]];
            default:
                return isset($_POST[$name]) ? $_POST[$name] : $def;
        }
    }

    public function param(string $name = null, $def = null)
    {
        if (empty($name)) {
            return $_REQUEST;
        }
        $type = '';
        if (preg_match('@^(.*):([abfis])@', $name, $m)) {
            $type = $m[2];
            $name = $m[1];
        }
        switch ($type) {
            case 's':
                if (!isset($_REQUEST[$name])) {
                    if (is_string($def)) {
                        return $def;
                    }
                    return '';
                }
                if (is_string($_REQUEST[$name])) {
                    return $_REQUEST[$name];
                }
                return strval($_REQUEST[$name]);
            case 'b':
                if (!isset($_REQUEST[$name])) {
                    if (is_bool($def)) {
                        return $def;
                    }
                    if (is_string($def)) {
                        return $def == '1' || $def == 'on' || $def == 'yes' || $def == 'true';
                    }
                    return false;
                }
                return $_REQUEST[$name] == '1' || $_REQUEST[$name] == 'on' || $_REQUEST[$name] == 'yes' || $_REQUEST[$name] == 'true';
            case 'f':
                if (!isset($_REQUEST[$name]) || !is_numeric($_REQUEST[$name])) {
                    if (is_double($def) || is_float($def)) {
                        return $def;
                    }
                    return 0;
                }
                return doubleval($_REQUEST[$name]);
            case 'i':
                if (!isset($_REQUEST[$name]) || !is_numeric($_REQUEST[$name])) {
                    if (is_integer($def)) {
                        return $def;
                    }
                    return 0;
                }
                return intval($_REQUEST[$name]);
            case 'a':
                if (!isset($_REQUEST[$name])) {
                    if (is_array($def)) {
                        return $def;
                    }
                    return [$def];
                }
                if (is_array($_REQUEST[$name])) {
                    return $_REQUEST[$name];
                }
                return [$_REQUEST[$name]];
            default:
                return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $def;
        }
    }

    public function getSession(string $name = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($name)) {
            return $_SESSION;
        }
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    public function setSession(string $name, $value)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$name] = $value;
    }

    public function getCookie(string $name)
    {
        return isset($_COOKIE["name"]) ? $_COOKIE["name"] : null;
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

    public function req(string $method, string $name, $def = null)
    {
        $method = strtolower($method);
        if ($method == 'get') {
            return $this->get($name, $def);
        } else if ($method == 'post') {
            return $this->post($name, $def);
        } else {
            return $this->param($name, $def);
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
                'json' => 'application/json',
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

    protected function config($name, $def = null)
    {
        return Config::get($name, $def);
    }

    public function isGet()
    {
        return $this->isMethod('get');
    }

    public function isMethod($method)
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