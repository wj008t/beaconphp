<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 14:05
 */

namespace beacon;


class Field
{

    private $form = null;
    //扩展属性
    private $extends = [];

    private $refClass = null;

    //基本属性
    public $label = '';
    public $name = '';
    public $error = null;
    public $close = false;
    public $offEdit = false;
    public $value = null;
    public $default = null;
    public $type = 'text';
    public $varType = 'string';
    public $remoteFunc = null;
    public $dynamic = null;
    //控件属性
    public $boxName = '';
    public $boxId = '';
    public $boxClass = null;
    //视图属性
    public $viewTabIndex = '';
    public $viewTabShared = false;
    public $viewClose = false;
    public $viewMerge = 0;
    //数据属性
    public $dataValOff = null;
    public $dataVal = null;
    public $dataValMsg = null;

    public function __construct(Form $form, array $field = [])
    {
        if ($field == null) {
            $field = [];
        }
        $this->refClass = new \ReflectionClass(get_class($this));
        foreach ($field as $key => $value) {
            $key = Utils::attrToCamel($key);
            if (preg_match('@Func$@', $key)) {
                $this->setValue($key, $value);
                continue;
            }
            if ($value instanceof \Closure) {
                $value = call_user_func($value, $this);
            }
            $this->setValue($key, $value);
        }
        $this->boxName = empty($this->boxName) ? $this->name : $this->boxName;
        $this->boxId = empty($this->boxId) ? $this->boxName : $this->boxId;
        //设置默认值
        $config = Config::get('form.field_default');
        foreach ($config as $key => $value) {
            $key = Utils::attrToCamel($key);
            if (preg_match('@Func$@', $key)) {
                continue;
            }
            $cur_value = $this->getValue($key);
            if (!($cur_value === null || (is_string($cur_value) && $cur_value === ''))) {
                continue;
            }
            if ($value instanceof \Closure) {
                $value = call_user_func($value, $this);
            }
            $this->setValue($key, $value);
        }
        $this->form = $form;
    }

    private function setValue($name, $value)
    {
        if ($this->refClass->hasProperty($name)) {
            $prop = $this->refClass->getProperty($name);
            if ($prop->isPublic()) {
                $prop->setValue($this, $value);
            }
        } else {
            $this->extends[$name] = $value;
        }
    }

    private function getValue($name)
    {
        $value = null;
        if ($this->refClass->hasProperty($name)) {
            $prop = $this->refClass->getProperty($name);
            if ($prop->isPublic()) {
                $value = $prop->getValue($this);
            }
        } else {
            $value = isset($this->extends[$name]) ? $this->extends[$name] : null;
        }
        return $value;
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

    public function getBoxData()
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

    public function getBoxAttribute()
    {
        $data = [];
        $refClass = new \ReflectionClass(get_class($this));
        $props = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $value = $prop->getValue($this);
            if ($value !== null && $value !== '') {
                $name = $prop->getName();
                if (preg_match('@^box([A-Z].*)$@', $name, $m)) {
                    $name = Utils::camelToAttr($m[1]);
                    $data[$name] = $value;
                }
            }
        }
        foreach ($this->extends as $name => $value) {
            if ($value !== null && $value !== '') {
                if (preg_match('@^box([A-Z].*)$@', $name, $m)) {
                    $name = Utils::camelToAttr($m[1]);
                    $data[$name] = $value;
                }
            }
        }
        if ($this->value !== null && $this->value !== '') {
            if (is_array($this->value)) {
                $data['value'] = json_encode($this->value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($this->value)) {
                $data['value'] = $this->value ? 1 : 0;
            } else {
                $data['value'] = $this->value;
            }
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

    public function explodeAttr(&$base = [], &$args = [], $filter = null)
    {
        if ($base == null) {
            $base = [];
        }
        $attributes = $this->getBoxAttribute();
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                $key = Utils::camelToAttr($key);
                //排除隐藏的类型和数据绑定类型
                if (preg_match('/^(@|data-)/', $key)) {
                    continue;
                }
                $attributes[$key] = $val;
            }
        }
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text';
        }
        foreach ($attributes as $name => $val) {
            if ($filter != null && is_callable($filter)) {
                if (!call_user_func($filter, $name, $val)) {
                    continue;
                }
            } else {
                if ($val === null || $val === '') {
                    continue;
                }
            }
            if (is_array($val)) {
                array_push($base, $name . '="' . htmlspecialchars(json_encode($val, JSON_UNESCAPED_UNICODE)) . '"');
            } else {
                array_push($base, $name . '="' . htmlspecialchars($val) . '"');
            }
        }
    }

    public function explodeData(&$base = [], &$args = [], $filter = null)
    {
        if ($base == null) {
            $base = [];
        }
        $data = $this->getBoxData();
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                $key = Utils::camelToAttr($key);
                //排除隐藏的类型和数据绑定类型
                if (!preg_match('/^data-(.*)$/', $key, $match)) {
                    continue;
                }
                $key = $match[1];
                $data[$key] = $val;
            }
        }

        foreach ($data as $name => $val) {
            if ($filter != null && is_callable($filter)) {
                if (!call_user_func($filter, $name, $val)) {
                    continue;
                }
            } else {
                if ($val === null || (is_string($val) && $val === '')) {
                    continue;
                }
            }
            if (is_array($val)) {
                array_push($base, 'data-' . $name . '="' . htmlspecialchars(json_encode($val, JSON_UNESCAPED_UNICODE)) . '"');
            } else {
                array_push($base, 'data-' . $name . '="' . htmlspecialchars($val) . '"');
            }
        }
    }

    public function box($args = null)
    {
        if ($args === null || !is_array($args)) {
            $args = [];
        }
        $box = Form::getBoxInstance($this->type);
        if ($box === null) {
            throw new \Exception('Unsupported input box type:' . $this->type);
        }
        return $box->code($this, $args);
    }

}

