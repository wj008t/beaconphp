<?php

namespace sdopx\libs;

use sdopx\Sdopx;

class Source
{

    //加载模板代码===
    public $content = null;
    public $lenght = 0;
    public $isload = false;
    //资源类型
    public $type = 'file';
    //资源名称
    public $name = null;
    //资源路径
    public $filepath = null;
    //模板标识
    public $tplname = null;
    //编译偏移位置
    public $offset = 0;
    //资源是否存在
    public $exists = null;
    //资源UID
    public $uid = null;
    //资源加载器
    public $resource = null;
    //引擎
    public $sdopx = null;
    //所有brock块
    public $blocks = null;
    //片段编译结束尾部位置
    public $endon = 0;
    //资源的标记
    public $left_delimiter = '{';
    public $right_delimiter = '}';
    public $end_literal = null;

    /**
     * 创建资源
     * @param type $resource
     * @param \sdopx\libs\Sdopx $sdopx
     * @param type $tplname
     * @param \sdopx\libs\type $type
     * @param \sdopx\libs\type $name
     */
    function __construct($resource, Sdopx $sdopx, $tplname, $type = 'file', $name = '')
    {
        $this->resource = $resource;
        $this->sdopx = $sdopx;  //模板引擎
        $this->name = $name; //模板名称
        $this->tplname = $tplname; //模板名称
        $this->uid = sha1($tplname . ',' . $type);
        $this->type = $type;
        $this->left_delimiter = $this->sdopx->left_delimiter;
        $this->right_delimiter = $this->sdopx->right_delimiter;

        if ($this->type == 'file') {
            if ($this->name != '' && $this->name[0] == '@') {
                $this->name = Utils::path($this->sdopx->getTemplateDir('common'), substr($this->name, 1));
            }
            if (file_exists($this->name)) {
                $this->filepath = realpath($this->name);
            } else {
                $tempdir = $this->sdopx->getTemplateDir();
                foreach ($tempdir as $path) {
                    $filename = Utils::path($path, $this->name);
                    if (file_exists($filename)) {
                        $this->filepath = realpath($filename);
                        break;
                    }
                }
                if ($this->filepath == null) {
                    throw new \sdopx\SdopxException("Sdopx 已经翻遍整个模板目录也没有找到编译所需的模板文件 {$this->tplname} .");
                }
            }
        } else {
            $this->filepath = $this->name;
        }
    }

    //资源加载内容
    public function load()
    {
        if ($this->type == 'file') {
            $this->resource->fetch($this->filepath, $content, $timestamp, $this->sdopx);
        } else {
            $this->resource->fetch($this->name, $content, $timestamp, $this->sdopx);
        }
        if ($content) {
            if (isset(Sdopx::$regfilters['pre'])) {
                foreach (Sdopx::$regfilters['pre'] as $func) {
                    $content = call_user_func($func, $content, $this->sdopx);
                }
            }
            $this->timestamp = $timestamp;
            $this->content = $content;
            $this->lenght = strlen($content);
            $this->isload = true;
            $this->exists = true;
        }
    }

    function __get($name)
    {
        if ($name == 'timestamp') {
            if ($this->type == 'file') {
                $this->timestamp = $this->resource->fetchTimestamp($this->filepath, $this->sdopx);
            } else {
                $this->timestamp = $this->resource->fetchTimestamp($this->name, $this->sdopx);
            }
            return $this->timestamp;
        }
        return null;
    }

    public function getLine()
    {
        if ($this->offset == 0) {
            return 1;
        }
        return substr_count($this->content, "\n", 0, $this->offset) + 1;
    }

    //移到Block标签尾部
    public function moveBlockToEnd(Compile $compler, $name, $offset = 0)
    {
        if ($offset == 0) {
            $offset = $this->offset;
        }
        if ($this->blocks === null) {
            Lexer::findBrock($this, $compler);
        }
        if (!isset($this->blocks[$name])) {
            return false;
        }
        if (count($this->blocks[$name]) == 1) {
            $block = $this->blocks[$name][0];
        } else {
            $block = null;
            foreach ($this->blocks[$name] as $temp) {
                if ($temp['start'] == $offset) {
                    $block = $temp;
                    break;
                }
            }
        }
        if ($block === null) {
            return false;
        }
        $this->offset = $block['end'];
    }

}
