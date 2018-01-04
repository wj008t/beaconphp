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

    public $req = null;
    public $res = null;
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
    public $_session = null;
    public $_ssid = null;
    public $_route = null;
    private $_out = [];
    private static $session = null;
    /**
     * @var Mysql
     */
    private $_db = null;
    private $content_type = 'text/html; charset=utf-8';

    public function __construct($request = null, $response = null)
    {
        $this->req = $request;
        $this->res = $response;
        if ($this->req) {
            $this->_get = $this->req->get == null ? [] : $this->req->get;
            $this->_post = $this->req->post == null ? [] : $this->req->post;
            foreach ($this->_get as $key => $value) {
                $this->_param[$key] = $value;
            }
            foreach ($this->_post as $key => $value) {
                $this->_param[$key] = $value;
            }
            $this->_files = $this->req->files == null ? [] : $this->req->files;
            $this->_cookie = $this->req->cookie == null ? [] : $this->req->cookie;
            foreach ($this->req->server as $key => $value) {
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
        if (IS_CLI && defined('HTTP_SWOOLE') && HTTP_SWOOLE) {
            $cookie = $this->getCookie('BEACON_SESSION');
            if (!empty($cookie)) {
                $this->_ssid = md5($cookie);
            }
        }
    }

    public function __destruct()
    {
        if (IS_CLI && HTTP_SWOOLE) {
            if ($this->_ssid === null) {
                return;
            }
            if (self::$session === null) {
                $size = Config::get('session.size', 1024);
                $length = Config::get('session.length', 200);
                self::$session = new \swoole_table($size);
                self::$session->column('expire', \swoole_table::TYPE_INT, 4);
                self::$session->column('data', \swoole_table::TYPE_STRING, $length);
                self::$session->create();
            }
            $item = self::$session->get($this->_ssid);
            if ($item == null && $this->_session === null) {
                return;
            }
            if (!is_array($item)) {
                $item = [];
            }
            $timeout = Config::get('session.timeout', 3600);
            $item['expire'] = time() + $timeout;
            if ($this->_session !== null) {
                if (!empty($item['data'])) {
                    $data = unserialize($item['data']);
                    $data = array_merge($data, $this->_session);
                    $item['data'] = serialize($data);
                } else {
                    $item['data'] = serialize($this->_session);
                }
            }
            self::$session->set($this->_ssid, $item);
            $this->_session = null;
        }
    }

    public function getDataBase()
    {
        if ($this->_db) {
            return $this->_db;
        }
        $driver = Config::get('db.db_driver', 'Mysql');
        if ($driver == 'Mysql') {
            $host = Config::get('db.db_host', '127.0.0.1');
            $port = Config::get('db.db_port', 3306);
            $name = Config::get('db.db_name', '');
            $user = Config::get('db.db_user', '');
            $pass = Config::get('db.db_pwd', '');
            $prefix = Config::get('db.db_prefix', 'sl_');
            $this->_db = new Mysql($host, $port, $name, $user, $pass, $prefix);
        }
        return $this->_db;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function write(string $data = null)
    {
        if ($this->res) {
            if ($data === null) {
                return;
            } else {
                $this->_out[] = $data;
            }
            return;
        }
        echo $data;
    }

    public function end(string $data = null)
    {
        if ($this->res) {
            foreach ($this->_out as $item) {
                $this->res->write($item);
            }
            if ($data === null) {
                $this->res->end();
            } else {
                $this->res->end($data);
            }
            return;
        }
        echo $data;
    }

    public function getHeader(string $name = null)
    {
        if ($this->header == null) {
            $this->header = [];
            if ($this->req !== null) {
                $this->header = $this->req->header;
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
        if ($this->res !== null) {
            return $this->res->header($name, $value);
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
        return isset($this->_cookie[$name]) ? $this->_cookie[$name] : null;
    }

    public function setCookie(string $name, $value, $options = null)
    {
        if ($options === null) {
            if ($this->res) {
                return $this->res->cookie($name, $value);
            }
            return setcookie($name, $value);
        }
        if (is_integer($options)) {
            if ($this->res) {
                return $this->res->cookie($name, $value, $options);
            }
            return setcookie($name, $value, $options);
        }
        $expire = isset($options['expire']) ? intval($options['expire']) : 0;
        $path = isset($options['path']) ? $options['path'] : '/';
        $domain = isset($options['domain']) ? $options['domain'] : '';
        $secure = isset($options['secure']) ? boolval($options['secure']) : false;
        $httponly = isset($options['httponly ']) ? boolval($options['httponly ']) : false;
        if ($this->res) {
            return $this->res->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }

        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);

    }

    public function getSession(string $name = null)
    {
        if (IS_CLI && defined('HTTP_SWOOLE') && HTTP_SWOOLE) {
            if ($this->_session === null) {
                if (self::$session === null) {
                    return null;
                }
                if ($this->_ssid === null) {
                    return null;
                }
                $item = self::$session->get($this->_ssid);
                if (!is_array($item)) {
                    return null;
                }
                if ($item['expire'] < time()) {
                    self::$session->del($this->_ssid);
                    return null;
                }
                $data = unserialize($item['data']);
                if (!is_array($data)) {
                    return null;
                }
                $this->_session = $data;
            }
            if (empty($name)) {
                return $this->_session;
            }
            return isset($this->_session[$name]) ? $this->_session[$name] : null;
        } else {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            if (empty($name)) {
                return $_SESSION;
            }
            return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
        }
    }

    public function setSession(string $name, $value)
    {
        if (IS_CLI && HTTP_SWOOLE) {
            if ($this->_ssid === null) {
                $cookie = Utils::randWord(20);
                $this->setCookie('BEACON_SESSION', $cookie, ['path' => '/']);
                $this->_ssid = md5($cookie);
            }
            if ($this->_session === null) {
                $this->_session = [];
            }
            $this->_session[$name] = $value;
            return;
        } else {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION[$name] = $value;
        }
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