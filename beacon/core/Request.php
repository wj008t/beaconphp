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

    /**
     * @var HttpContext
     */
    public $context = null;

    public function __construct(HttpContext $context)
    {
        $this->context = $context;
    }

    public function get(string $name = null, $def = null)
    {
        return $this->req($this->context->_get, $name, $def);
    }

    public function post(string $name = null, $def = null)
    {
        return $this->req($this->context->_post, $name, $def);
    }

    public function param(string $name = null, $def = null)
    {
        return $this->req($this->context->_param, $name, $def);
    }

    public function getCookie(string $name)
    {
        return $this->context->getCookie($name);
    }

    public function setCookie(string $name, $value, $options = null)
    {
        return $this->context->setCookie($name, $value, $options);
    }

    public function getSession(string $name = null)
    {
        return $this->context->getSession($name);
    }

    public function setSession(string $name, $value)
    {
        return $this->context->setSession($name, $value);
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
            return $this->context->_files;
        }
        return isset($this->context->_files[$name]) ? $this->context->_files[$name] : null;
    }

    public function route(string $name = null, $def = null)
    {
        return $this->context->route($name, $def);
    }

    public function getIP(bool $proxy = false, bool $forward = false)
    {
        $ip = '';
        if ($proxy) {
            if ($forward) {
                $forwardIP = $this->context->getHeader('x-forwarded-for');
                if (!empty($forwardIP)) {
                    $temps = explode(',', $forwardIP);
                    foreach ($temps as $item) {
                        $item = trim($item);
                        if (filter_var($item, FILTER_VALIDATE_IP)) {
                            return $item;
                        }
                    }
                }
                $ip = $this->context->getHeader('x-real-ip');
            }
        } else {
            if (isset($this->context->_server['REMOTE_ADDR'])) {
                $ip = $this->context->_server['REMOTE_ADDR'];
            }
        }
        if (empty($ip)) {
            return '127.0.0.1';
        }
        return $ip;
    }


    public function isGet()
    {
        return $this->isMethod('get');
    }

    public function isMethod($method)
    {
        return strtolower($this->context->_server['REQUEST_METHOD']) == strtolower($method) ? true : false;
    }

    public function getMethod()
    {
        return strtolower($this->context->_server['REQUEST_METHOD']);
    }

    public function isPost()
    {
        return $this->isMethod('post');
    }

    public function isAjax()
    {
        if (isset($this->context->_server['DOCUMENT_URI']) && preg_match('@\.json$@i', $this->context->_server['DOCUMENT_URI'])) {
            return true;
        }
        return strtolower($this->context->getHeader('x-requested-with')) === 'xmlhttprequest';
    }

    public function getReferrer()
    {
        return $this->context->getReferrer();
    }

}