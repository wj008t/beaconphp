<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 15:41
 */

namespace core;


class Form
{
    /**
     * @var $fields Field[];
     */
    private $fields = [];
    /**
     * @var $boxInstance \widget\BoxInterface[]
     */
    private $boxInstance = [];
    //基本属性
    public $title = '';
    public $caption = '';
    public $type = '';
    //视图属性
    public $viewUseTab = false;
    public $viewTabs = [];
    public $viewCurrentTabIndex = '';
    public $viewTabSplit = false;
    /**
     * @var Validate
     */
    private $validate = null;

    private $inited = false;

    private $cacheUsingFields = [];
    protected $hideBox = [];

    public function __construct($type = '')
    {
        $this->type = $type;
        if ($this->type == 'add' || $this->type = 'edit') {
            $this->initialize();
        }
    }

    public function initialize()
    {
        if ($this->inited) {
            return;
        }
        $this->inited = true;
        $load = $this->load();
        if (is_array($load)) {
            foreach ($load as $name => $field) {
                if (!is_array($field)) {
                    continue;
                }
                $this->addField($name, $field);
            }
        }
    }

    public function load()
    {
        return [];
    }

    public function isAdd()
    {
        return $this->type = 'add';
    }

    public function isEdit()
    {
        return $this->type = 'edit';
    }

    public function addField(string $name, $field, string $before = null)
    {
        if ($field instanceof Field) {
            $field->name = $name;
        } else if (is_array($field)) {
            $field['name'] = $name;
            $field = new Field($this, $field);
        } else {
            return $this;
        }
        if (!empty($before) && isset($this->fields[$before])) {
            $temps = [];
            foreach ($this->fields as $key => $item) {
                if ($key == $before) {
                    $temps[$name] = $field;
                }
                $temps[$key] = $item;
            }
            $this->fields = $temps;
        } else {
            $temps[$name] = $field;
        }
        return $this;

    }

    public function getField(string $name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    public function removeField(string $name)
    {
        $field = isset($this->fields[$name]) ? $this->fields[$name] : null;
        if ($field !== null) {
            unset($this->fields[$name]);
        }
        return $field;
    }

    public function getError(string $name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name]->error : '';
    }

    public function setError($name, $error)
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->error = $error;
        }
    }

    public function removeError($name)
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->error = null;
        }
    }

    public function getFirstError()
    {
        $fields = $this->getCurrentFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                return $field->error;
            }
        }
        return null;
    }

    public function getAllError()
    {
        $erors = [];
        $fields = $this->getCurrentFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $erors[$name] = $field->error;
            }
        }
        return $erors;
    }

    public function cleanAllErrors()
    {
        $fields = $this->getCurrentFields();
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $field->error = null;
            }
        }
    }

    public function emptyFieldsValue()
    {
        $fields = $this->fields;
        foreach ($fields as $name => $field) {
            $field->value = null;
        }
    }

    public function addHideBox(string $name, $value)
    {
        $this->hideBox[$name] = $value;
    }

    public function getHideBox(string $name = null)
    {
        if (empty($name)) {
            return $this->hideBox;
        }
        if (isset($this->hideBox[$name])) {
            return $this->hideBox[$name];
        }
    }

    public function getCurrentFields()
    {
        if ($this->viewUseTab && $this->viewTabSplit) {
            if (!empty($this->viewCurrentTabIndex)) {
                $this->viewCurrentTabIndex = Request::instance()->get('tabIndex:s');
                return $this->getTabFields($this->viewCurrentTabIndex);
            }
        }
        return $this->getTabFields();
    }

    private function getTabFields(string $tabIndex = null)
    {
        if (empty($tabIndex)) {
            return $this->fields;
        }
        if (isset($this->cacheUsingFields[$tabIndex])) {
            return $this->cacheUsingFields[$tabIndex];
        }
        $temp = [];
        foreach ($this->fields as $name => $field) {
            if (!empty($field->viewTabIndex) && $field->viewTabIndex == $tabIndex) {
                $temp[$name] = $field;
            }
        }
        $this->cacheUsingFields[$tabIndex] = $temp;

    }

    /**
     * @param string $type
     * @return \widget\BoxInterface
     * @throws \Exception
     */
    public function getBoxInstance(string $type)
    {
        if (empty($type)) {
            return null;
        }
        if (isset($this->boxInstance[$type])) {
            return $this->boxInstance[$type];
        }
        $class = '\\widget\\' . Utils::toCamel($type);
        if (!class_exists($class)) {
            return null;
        }
        $reflect = new \ReflectionClass($class);
        if (!$reflect->implementsInterface('\\widget\\BoxInterface')) {
            return null;
        }
        $this->boxInstance[$type] = new $class();
        return $this->boxInstance[$type];
    }

    public function getValues(array $allow = null)
    {
        $values = [];
        $fields = $this->getCurrentFields();
        foreach ($fields as $name => $field) {
            if ($allow != null) {
                if (!in_array($name, $allow)) {
                    continue;
                }
            }
            if ($field->close || ($field->offEdit && $this->type = 'edit')) {
                continue;
            }
            $box = $this->getBoxInstance($field->type);
            if ($box != null) {
                $box->fill($field, $values);
            } else {
                $values[$name] = $field->value;
            }
        }
    }

    public function initValues(array $values = null, bool $force = false)
    {
        if ($values == null) {
            return;
        }
        if (method_exists($this, 'beforeInitValues')) {
            $values = $this->beforeInitValues($values);
        }
        $fields = $this->getCurrentFields();
        foreach ($fields as $name => $field) {
            if ($field->close) {
                continue;
            }
            if (!$force && $field->value !== null) {
                continue;
            }
            $box = $this->getBoxInstance($field->type);
            if ($box != null) {
                $box->init($field, $values);
            } else {
                $field->value = isset($values[$name]) ? $values[$name] : null;
            }
        }
        if (method_exists($this, 'afterInitValues')) {
            $values = $this->afterInitValues($values);
        }
    }

    public function autoComplete($method = '')
    {
        $method = strtoupper($method);
        $fields = $this->getCurrentFields();
        foreach ($fields as $name => $field) {
            if ($field->close || ($field->offEdit && $this->type = 'edit')) {
                continue;
            }
            if ($field->viewClose) {
                $field->value = $field->default;
                continue;
            }
            $box = $this->getBoxInstance($field->type);
            if ($box != null) {
                $box->assign($field, $method);
            } else {
                $boxName = $field->boxName;
                $request = Request::instance();
                if ($method == 'GET') {
                    $func = new \ReflectionMethod($request, 'get');
                } elseif ($method == 'POST') {
                    $func = new \ReflectionMethod($request, 'post');
                } else {
                    $func = new \ReflectionMethod($request, 'param');
                }
                switch ($field->varType) {
                    case 'bool':
                    case 'boolean':
                        $field->value = $func->invoke($request, $boxName . ':b', $field->default);
                        break;
                    case 'int':
                    case 'integer':
                        $val = $func->invoke($request, $boxName . ':s', $field->default);
                        if (preg_match('@[+-]?\d*\.\d+@', $field->default)) {
                            $field->value = $func->invoke($request, $boxName . ':f', $field->default);
                        } else {
                            $field->value = $func->invoke($request, $boxName . ':i', $field->default);
                        }
                        break;
                    case 'double':
                    case 'float':
                        $field->value = $func->invoke($request, $boxName . ':f', $field->default);
                        break;
                    case 'string':
                        $field->value = $func->invoke($request, $boxName . ':s', $field->default);
                        break;
                    case 'array':
                        $field->value = $func->invoke($request, $boxName . ':a', $field->default);
                        break;
                    default :
                        $field->value = $func->invoke($request, $boxName, $field->default);
                        break;
                }
            }
        }
        return $this->getValues();
    }

    public function validation(&$errors)
    {
        $fields = $this->getCurrentFields();
        if (method_exists($this, 'beforeValid')) {
            $this->beforeValid($fields);
        }
        $result = true;
        foreach ($fields as $field) {
            $this->validDynamic($field);
        }
        foreach ($fields as $name => $field) {
            if (!empty($field->error)) {
                $result = false;
                continue;
            }
            if ($field->close || ($field->offEdit && $this->type = 'edit')) {
                continue;
            }
            $ret = $this->getValidateInstance()->checkField($field);
            if (!$ret) {
                $result = false;
            }
        }
        if (method_exists($this, 'afterValid')) {
            $this->afterValid($fields);
        }
        if (!$result) {
            $errors = $this->getAllError();
        }
        return $result;
    }

    public function getValidateInstance()
    {
        if ($this->validate == null) {
            $this->validate = new Validate();
        }
        return $this->validate;
    }

    private function createDynamic(Field $field)
    {
        if ($field->dynamic === null || !is_array($field->dynamic)) {
            return;
        }
        $dynamic = [];
        foreach ($field->dynamic as $item) {
            $temp = [];
            $hasCondition = false;
            foreach (['eq', 'neq', 'in', 'nin'] as $qkey) {
                if (!isset($temp[$qkey])) {
                    continue;
                }
                $hasCondition = true;
                $temp[$qkey] = $item[$qkey];
            }
            if (!$hasCondition) {
                continue;
            }
            $hasType = false;
            foreach (['hide', 'show', 'off-val', 'on-val'] as $type) {
                if (!isset($temp[$type])) {
                    continue;
                }
                if (!(is_string($temp[$type]) || is_array($temp[$type]))) {
                    continue;
                }
                $tempIds = [];
                $typeitems = is_string($temp[$type]) ? explode(',', $temp[$type]) : $temp[$type];
                foreach ($typeitems as $name) {
                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    $box = $this->getField($name);
                    if ($box == null || empty($box->boxId)) {
                        continue;
                    }
                    $tempIds[] = $box->boxId;
                }
                if (count($tempIds) > 0) {
                    $temp[$type] = $tempIds;
                    $hasType = true;
                }
            }
            if (!$hasType) {
                continue;
            }
            $dynamic[] = $temp;
        }
        //设置 yee-module 属性
        if (count($dynamic) > 0) {
            if (isset($field->boxYeeModule)) {
                $module = explode(' ', $field->boxYeeModule);
                $module = array_filter($module, 'strlen');
                if (!in_array('dynamic', $module)) {
                    $module[] = 'dynamic';
                }
                $field->boxYeeModule = $module . join(' ');
            } else {
                $field->boxYeeModule = 'dynamic';
            }
        }
    }

    private function validDynamic(Field $field)
    {
        if ($field->dynamic === null || !is_array($field->dynamic)) {
            return;
        }
        $fields = $this->getCurrentFields();
        $value = $field->value;
        if (is_object($value)) {
            return;
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        foreach ($field->dynamic as $item) {

            if (!(
                isset($item['eq']) ||
                isset($item['neq']) ||
                isset($item['in']) ||
                isset($item['nin'])
            )) {
                continue;
            }
            if (!(
                isset($item['hide']) ||
                isset($item['show']) ||
                isset($item['off-val']) ||
                isset($item['on-val'])
            )) {
                continue;
            }
            if (isset($item['eq'])) {
                $bval = $item['eq'];
                if (is_array($bval)) {
                    $bval = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($bval != $value) {
                    continue;
                }
            }

            if (isset($item['neq'])) {
                $bval = $item['neq'];
                if (is_array($bval)) {
                    $bval = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($bval == $value) {
                    continue;
                }
            }

            if (isset($item['in'])) {
                $bval = $item['in'];
                if (!is_array($bval)) {
                    continue;
                }
                $tempval = [];
                foreach ($bval as $btemp) {
                    $tempval[] = strval($btemp);
                }
                if (!in_array(strval($value), $tempval)) {
                    continue;
                }
            }

            if (isset($item['nin'])) {
                $bval = $item['nin'];
                if (!is_array($bval)) {
                    continue;
                }
                $tempval = [];
                foreach ($bval as $btemp) {
                    $tempval[] = strval($btemp);
                }
                if (in_array(strval($value), $tempval)) {
                    continue;
                }
            }
            //校验item
            $temp = [];
            foreach (['hide', 'off-val'] as $type) {
                if (!isset($item[$type])) {
                    continue;
                }
                if (!(is_string($item[$type]) || is_array($item[$type]))) {
                    continue;
                }
                $item[$type] = is_string($temp[$type]) ? explode(',', $temp[$type]) : $temp[$type];
            }

            if (isset($item['hide'])) {
                foreach ($item['hide'] as $name) {
                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    $box = $this->getField($name);
                    if ($box == null || empty($box->boxId)) {
                        continue;
                    }
                    $box->dataValOff = true;
                    $box->close = true;
                }
            }

            if (isset($item['off-val'])) {
                foreach ($item['off-val'] as $name) {
                    if (!is_string($name) || empty($name)) {
                        continue;
                    }
                    $box = $this->getField($name);
                    if ($box == null || empty($box->boxId)) {
                        continue;
                    }
                    $box->dataValOff = true;
                }
            }

        }

    }

    public function getViewFields()
    {
        $fields = $this->getCurrentFields();
        //修正显示
        foreach ($fields as $name => $field) {
            //处理视图的开关默认值
            if ($field->viewClose === null) {
                if ($field->close) {
                    $field->viewClose = true;
                    continue;
                } else {
                    $field->viewClose = false;
                }
            }
            //隐藏字段
            if ($field->type = 'hide') {
                $field->viewClose = true;
                $this->addHideBox($field->boxName, $field->value);
            }
        }
        $keys = array_keys($fields);
        $temp = [];
        $klen = count($keys);
        for ($idx = 0; $idx < $klen; $idx++) {
            $name = $keys[$idx];
            $field = $fields[$name];
            if ($idx == 0) {
                $field->viewMerge = 0;
            }
            //如果这一行合并到上一行
            if ($field->viewMerge == -1) {
                if ($idx - 1 >= 0) {
                    $prevField = $fields[$keys[$idx - 1]];
                    $prevField->next = $field;
                } else {
                    $field->viewMerge = 0;
                }
            }
            //合并到下一行
            if ($field->viewMerge == 1) {
                if ($idx + 1 < $klen) {
                    $nextField = $fields[$keys[$idx + 1]];
                    $nextField->prev = $field;
                } else {
                    $field->viewMerge = 0;
                }
            }
            //不合并
            if ($field->viewMerge == 0 && !$field->viewClose) {
                $temp[$name] = $field;
            }
            if (!$field->viewClose) {
                $this->createDynamic($field);
            }
        }
        return $temp;
    }


}