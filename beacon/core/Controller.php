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
}