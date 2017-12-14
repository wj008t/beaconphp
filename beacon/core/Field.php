<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 14:05
 */

namespace core;


class Field
{

    private $form = null;
    //扩展属性
    private $extends = [];

    //基本属性
    public $label = '';
    public $name = '';
    public $error = '';
    public $close = false;
    public $offEdit = false;
    public $value = null;
    public $default = null;
    public $type = 'text';
    public $varType = 'string';
    public $remoteFunc = null;
    //控件属性
    public $inputName = '';
    public $inputId = '';
    //视图属性
    public $viewTabName = '';
    public $viewTabShared = false;
    public $viewClose = false;
    public $viewMerge = 0;
    public $viewDynamic = null;
    //数据属性
    public $dataValOff = null;
    public $dataVal = null;
    public $dataValMsg = null;

    public function __construct($form, array $field = [])
    {
        $refClass = new \ReflectionClass(get_class($this));
        foreach ($field as $key => $value) {
            $key = Utils::attrToCamel($key);//小驼峰
            if ($refClass->hasProperty($key)) {
                $prop = $refClass->getProperty($key);
                if ($prop->isPublic()) {
                    $prop->setValue(get_class($this), $value);
                }
            } else {
                $this->extends[$key] = $value;
            }
        }
        $this->inputName = empty($this->inputName) ? $this->name : $this->inputName;
        $this->inputId = empty($this->boxId) ? $this->inputName : $this->inputId;
        $this->form = $form;
    }

    public function __set($name, $value)
    {
        $this->extends[$name] = $value;
    }

    public function __get($name)
    {
        if (!isset($this->extends[$name])) {
            return null;
        }
        return $this->extends[$name];
    }

    public function __isset($name)
    {
        return isset($this->extends[$name]);
    }

    public function __unset($name)
    {
        return __unset($this->extends[$name]);
    }

    public function getForm()
    {
        return $this->form;
    }

    public function getInputData()
    {
        $data = [];
        $refClass = new \ReflectionClass(get_class($this));
        $props = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $value = $prop->getValue($this);
            if ($value !== null) {
                $name = $prop->getName();
                if (preg_match('@^data([A-Z].*)$@', $name, $m)) {
                    $name = Utils::camelToAttr($m[1]);
                    $data[$name] = $value;
                }
            }
        }
        foreach ($this->extends as $name => $value) {
            if ($value !== null) {
                if (preg_match('@^data([A-Z].*)$@', $name, $m)) {
                    $name = Utils::camelToAttr($m[1]);
                    $data[$name] = $value;
                }
            }
        }
        if (!empty($this->error)) {
            $data['val-fail'] = $this->error;
        }
        return $data;
    }

    public function getInputAttribute()
    {
        $data = [];
        $refClass = new \ReflectionClass(get_class($this));
        $props = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $value = $prop->getValue($this);
            if ($value !== null && $value !== '') {
                $name = $prop->getName();
                if (preg_match('@^input([A-Z].*)$@', $name, $m)) {
                    $name = Utils::camelToAttr($m[1]);
                    $data[$name] = $value;
                }
            }
        }
        foreach ($this->extends as $name => $value) {
            if ($value !== null && $value !== '') {
                if (preg_match('@^input([A-Z].*)$@', $name, $m)) {
                    $name = Utils::camelToAttr($m[1]);
                    $data[$name] = $value;
                }
            }
        }
        if ($this->value !== null && $this->value !== '') {
            $data['value'] = $this->value;
        }
        if ($this->form != null && $this->form->type = 'edit') {
            if ($this->offEdit) {
                $data['disabled'] = 'disabled';
            }
        }
        if ($this->form != null && $this->form->type = 'add') {
            if (!isset($data['value']) && $this->default !== null && $this->default !== '') {
                $data['value'] = $this->default;
            }
        }
        return $data;
    }


}

