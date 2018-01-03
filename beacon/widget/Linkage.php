<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 17:22
 */

namespace widget;


use beacon\Field;
use beacon\Request;
use beacon\Utils;

class Linkage implements BoxInterface
{

    public function code(Field $field, $args)
    {
        $value = isset($args['value']) ? $args['value'] : $field->value;
        if ($value === null) {
            $value = $field->default;
        }
        if ($value == null || !is_array($value)) {
            $value = [];
        }
        $args['value'] = '';
        if (count($value) > 0) {
            $args['value'] = $value;
        }
        if ($field->names !== null && is_array($field->names)) {
            foreach ($field->names as $idx => $pname) {
                $level = $idx + 1;
                $args['data-name' . $level] = $pname;
            }
        }
        $args['yee-module'] = 'linkage';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    private function convertType($values, $type)
    {
        if ($values === null) {
            return null;
        }
        foreach ($values as &$value) {
            switch ($type) {
                case 'bool':
                case 'boolean':
                    $value = strval($value) === '1' || strval($value) === 'on' || strval($value) === 'yes' || strval($value) === 'true';
                    break;
                case 'int':
                case 'integer':
                    $value = intval($value);
                    break;
                case 'double':
                case 'float':
                    $value = floatval($value);
                    break;
                default :
                    break;
            }
        }
        return $values;
    }

    public function assign(Field $field, array $data)
    {
        $request = $field->getForm()->context->getRequest();
        if ($field->names !== null && is_array($field->names)) {
            $default = isset($field->default) ? $field->default : [];
            $values = [];
            foreach ($field->names as $idx => $name) {
                $def = isset($default[$idx]) ? $default[$idx] : null;
                $values[] = $request->req($data, $name . ':s', $def);
            }
            return $field->value = $this->convertType($values, $field->varType);
        }
        $boxName = $field->boxName;
        $values = $request->req($data, $boxName, null);
        if (is_array($values)) {
            return $field->value = $this->convertType($values, $field->varType);
        }
        if (Utils::isJsonString($values)) {
            $values = json_decode($values);
            if (is_array($values)) {
                return $field->value = $this->convertType($values, $field->varType);
            }
        }
        return $field->value = $this->convertType($field->default, $field->varType);

    }

    public function fill(Field $field, array &$values)
    {
        if ($field->names !== null && is_array($field->names)) {
            if ($field->value === null || count($field->value) != count($field->names)) {
                return;
            }
            foreach ($field->names as $idx => $name) {
                $values[$name] = $field->value[$idx];
            }
        }
        if ($field->value === null) {
            $values[$field->name] = '';
            return;
        }
        $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
    }

    public function init(Field $field, array $values)
    {
        if ($field->names !== null && is_array($field->names)) {
            $temps = [];
            foreach ($field->names as $idx => $name) {
                $temps[] = isset($values[$name]) ? '' : $values[$name];
            }
            return $field->value = $this->convertType($temps, $field->varType);
        }
        $temps = isset($values[$field->name]) ? '' : $values[$field->name];
        if (is_array($temps)) {
            return $field->value = $this->convertType($temps, $field->varType);
        }
        if (Utils::isJsonString($temps)) {
            $temps = json_decode($temps);
            if (is_array($temps)) {
                return $field->value = $this->convertType($temps, $field->varType);
            }
        }
        return $field->value = null;
    }
}