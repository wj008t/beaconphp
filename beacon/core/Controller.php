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
    /**
     * @var View
     */
    protected $view = null;
    /**
     * @var HttpContext
     */
    protected $context = null;

    /**
     * @var Mysql
     */
    public $db = null;

    public function __construct(HttpContext $context)
    {
        $this->context = $context;
        $this->db = $this->context->getDataBase();
    }

    public function __destruct()
    {
        $this->context->__destruct();
        $this->context = null;
    }

    protected function view()
    {
        if ($this->view == null) {
            $this->view = new View($this->context);
        }
        return $this->view;
    }

    protected function engine()
    {
        $view = $this->view();
        $view->initialize();
        return $view->engine;
    }

    protected function assign($key, $val = null)
    {
        return $this->view()->assign($key, $val);
    }

    protected function display($tplname)
    {
        return $this->view()->display($tplname);
    }

    protected function fetch($tplname)
    {
        return $this->view()->fetch($tplname);
    }

    protected function redirect($url)
    {
        $url = empty($url) ? '/' : $url;
        $this->context->setHeader('Location', $url);
        $this->exit();
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
        if ($this->context->getContentType() == 'application/json' || $this->context->getContentType() == 'text/json') {
            $this->context->write(json_encode($out));
            $this->exit();
        } else {
            if (empty($jump)) {
                $jump = $this->context->getReferrer();
            }
            if (empty($jump)) {
                $jump = '#';
            }
            $out['jump'] = $jump;
            $this->assign('info', $out);
            $this->context->write($this->fetch('@fail.tpl'));
            $this->exit();
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
        if ($this->context->getContentType() == 'application/json' || $this->context->getContentType() == 'text/json') {
            $this->context->write(json_encode($out));
            $this->exit();
        } else {
            if (empty($jump)) {
                $jump = $this->context->param('__BACK__');
            }
            if (empty($jump)) {
                $jump = $this->context->getReferrer();
            }
            if (empty($jump)) {
                $jump = '#';
            }
            $out['jump'] = $jump;
            $this->assign('info', $out);
            $this->context->write($this->fetch('@success.tpl'));
            $this->exit();
        }
    }

    /**
     *
     */
    public function exit()
    {
        if (IS_CLI) {
            throw new \beacon\RouteEndError('exit');
        } else {
            exit;
        }
    }
}