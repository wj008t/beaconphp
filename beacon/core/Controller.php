<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 20:40
 */

namespace beacon;


abstract class Controller
{
    public function initialize(Request $request = null)
    {
    }

    protected function engine()
    {
        View::instance()->initialize();
        return View::instance()->engine;
    }

    protected function assign($key, $val = null)
    {
        View::instance()->assign($key, $val);
    }

    protected function display($tplname)
    {
        return View::instance()->display($tplname);
    }

    protected function fetch($tplname)
    {
        return View::instance()->fetch($tplname);
    }

    protected function redirect($url)
    {
        $url = empty($url) ? '/' : $url;
        Request::instance()->setHeader('Location', $url);
        exit;
    }

    public function error($error, $code = null, $jump = null)
    {
        $out = [];
        $out['status'] = false;
        $out['code'] = $code;
        if ($jump != null) {
            $out['jump'] = $jump;
        }
        if (is_array($error)) {
            $out['formError'] = $error;
            reset($error);
            $out['error'] = current($error);
            $out['error'] = $out['error'] == null ? '错误' : $out['error'];
        } else {
            $out['error'] = $error;
        }
        if (Request::instance()->getContentType() == 'application/json' || Request::instance()->getContentType() == 'text/json') {
            echo json_encode($out);
            exit;
        } else {
            if (empty($jump)) {
                $jump = Request::instance()->getReferrer();
            }
            if (empty($jump)) {
                $jump = '#';
            }
            $out['jump'] = $jump;
            $this->assign('info', $out);
            $this->display('@fail.tpl');
            exit;
        }
    }

    public function success($message, $data = null, $jump = null)
    {
        $out = [];
        $out['status'] = true;
        $out['data'] = $data;
        $out['message'] = $message;
        if ($jump != null) {
            $out['jump'] = $jump;
        }
        if (Request::instance()->getContentType() == 'application/json' || Request::instance()->getContentType() == 'text/json') {
            echo json_encode($out);
            exit;
        } else {
            if (empty($jump)) {
                $jump = Request::instance()->param('__BACK__');
            }
            if (empty($jump)) {
                $jump = Request::instance()->getReferrer();
            }
            if (empty($jump)) {
                $jump = '#';
            }
            $out['jump'] = $jump;
            $this->assign('info', $out);
            $this->display('@success.tpl');
            exit;
        }
    }

    protected function get(string $name = null, $def = null)
    {
        return Request::instance()->get($name, $def);
    }

    protected function post(string $name = null, $def = null)
    {
        return Request::instance()->post($name, $def);
    }

    protected function param(string $name = null, $def = null)
    {
        return Request::instance()->param($name, $def);
    }

    protected function getSession(string $name = null, $def = null)
    {
        return Request::instance()->getSession($name, $def);
    }

    protected function setSession(string $name, $value)
    {
        return Request::instance()->setSession($name, $value);
    }

    protected function getCookie(string $name, $def = null)
    {
        return Request::instance()->getCookie($name, $def);
    }

    protected function setCookie(string $name, $value, $options)
    {
        return Request::instance()->setCookie($name, $value, $options);
    }


    protected function file(string $name = null)
    {
        return Request::instance()->file($name);
    }

    protected function route(string $name = null, $def = null)
    {
        return Request::instance()->route($name, $def);
    }

    protected function getHeader(string $name = null)
    {
        return Request::instance()->getHeader($name);
    }

    protected function setHeader(string $name, string $value, bool $replace = true, $http_response_code = null)
    {
        return Request::instance()->setHeader($name, $value, $replace, $http_response_code);
    }

    protected function getIP(bool $proxy = false, bool $forward = false)
    {
        return Request::instance()->getIP($proxy, $forward);
    }

    protected function getContentType(bool $whole = false)
    {
        return Request::instance()->getContentType($whole);
    }

    protected function setContentType(string $type, string $encoding = 'utf-8')
    {
        return Request::instance()->setContentType($type, $encoding);
    }

    protected function config(string $name, $def = null)
    {
        return Request::instance()->config($name, $def);
    }

    protected function isGet()
    {
        return Request::instance()->isGet();
    }

    protected function isMethod(string $method)
    {
        return Request::instance()->isMethod($method);
    }

    protected function getMethod()
    {
        return Request::instance()->getMethod();
    }

    protected function isPost()
    {
        return Request::instance()->isPost();
    }

    protected function isAjax()
    {
        return Request::instance()->isAjax();
    }

    protected function getReferrer()
    {
        return Request::instance()->getReferrer();
    }

}