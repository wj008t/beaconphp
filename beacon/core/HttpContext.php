<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2018/1/3
 * Time: 18:55
 */

namespace beacon;


class HttpContext
{

    public $origin_request = null;
    public $origin_response = null;
    /**
     * @var Request
     */
    private $request = null;
    public $header = null;
    public $_get = [];
    public $_post = [];
    public $_param = [];
    public $_files = [];
    public $_cookie = [];
    public $_server = [];
    public $_route = null;
    public static $session = [];
    private $content_type = 'text/html; charset=utf-8';


    public function __construct($request = null, $response = null)
    {
        $this->origin_request = $request;
        $this->origin_response = $response;
        if ($this->origin_request) {
            $this->_get = $this->origin_request->get == null ? [] : $this->origin_request->get;
            $this->_post = $this->origin_request->post == null ? [] : $this->origin_request->post;
            foreach ($this->_get as $key => $value) {
                $this->_param[$key] = $value;
            }
            foreach ($this->_post as $key => $value) {
                $this->_param[$key] = $value;
            }
            $this->_files = $this->origin_request->files == null ? [] : $this->origin_request->files;
            $this->_cookie = $this->origin_request->cookie == null ? [] : $this->origin_request->cookie;
            foreach ($this->origin_request->server as $key => $value) {
                $this->_server[strtoupper($key)] = $value;
            }
        } else {
            $this->_get = $_GET;
            $this->_post = $_POST;
            $this->_param = $_REQUEST;
            $this->_files = $_FILES;
            $this->_cookie = $_COOKIE;
            $this->_server = $_SERVER;
        }
        $this->request = new Request($this);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function write(string $data)
    {
        if ($this->origin_response) {
            $this->origin_response->write($data);
            return;
        }
        echo $data;
    }

    public function end(string $data)
    {
        if ($this->origin_response) {
            $this->origin_response->end($data);
            return;
        }
        echo $data;
    }

    public function getHeader(string $name = null)
    {
        if ($this->header == null) {
            $this->header = [];
            if ($this->origin_request !== null) {
                $this->header = $this->origin_request->header;
            } else {
                foreach ($_SERVER as $key => $value) {
                    if ('HTTP_' == substr($key, 0, 5)) {
                        $key = strtolower(str_replace('_', '-', substr($key, 5)));
                        $this->header[$key] = $value;
                    }
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
        if ($this->origin_response !== null) {
            return $this->origin_response->header($name, $value);
        }
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

    public function getCookie(string $name)
    {
        return isset($this->_cookie["name"]) ? $this->_cookie["name"] : null;
    }

    public function setCookie(string $name, $value, $options)
    {
        if ($options == null) {
            if ($this->origin_response) {
                return $this->origin_response->cookie($name, $value);
            }
            return setcookie($name, $value);
        }
        if (is_integer($options)) {
            if ($this->origin_response) {
                return $this->origin_response->cookie($name, $value, $options);
            }
            return setcookie($name, $value, $options);
        }
        $expire = isset($options['expire']) ? intval($options['expire']) : 0;
        $path = isset($options['path']) ? intval($options['path']) : '';
        $domain = isset($options['domain']) ? intval($options['domain']) : '';
        $secure = isset($options['secure']) ? intval($options['secure']) : false;
        $httponly = isset($options['httponly ']) ? intval($options['httponly ']) : false;
        if ($this->origin_response) {
            return $this->origin_response->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);

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

    public function getReferrer()
    {
        $referer = $this->getHeader('referer');
        if (empty($referer)) {
            $referer = $this->getHeader('referrer');
        }
        return $referer;
    }

    public function route(string $name = null, $def = null)
    {
        if (empty($name)) {
            return $this->_route;
        }
        if ($this->_route == null) {
            return $def;
        }
        if (isset($this->_route[$name])) {
            return $this->_route[$name];
        }
        return $def;
    }


}