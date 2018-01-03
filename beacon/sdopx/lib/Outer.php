<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/10/13
 * Time: 21:33
 */

namespace sdopx\lib;


use sdopx\Sdopx;

class Outer
{
    private $output = [];
    private $line = 0;
    private $src = '';
    public $_sdopx = null;

    public function __construct(Sdopx $_sdopx)
    {
        $this->_sdopx = $_sdopx;
    }

    public function text($code)
    {
        if (is_string($code)) {
            array_push($this->output, htmlspecialchars($code, ENT_QUOTES));
        } else {
            array_push($this->output, $code);
        }
    }

    public function html($code)
    {
        array_push($this->output, $code);
    }

    public function debug($line, $src)
    {
        $this->line = $line;
        $this->src = $src;
    }

    public function throw($err)
    {
        $this->_sdopx->rethrow($err, $this->line, $this->src);
    }

    public function getCode()
    {
        return join('', $this->output);
    }
}
