<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace widget;

use beacon\Field;
use beacon\Request;

class Hidden implements BoxInterface
{
    public function code(Field $field, $args)
    {
        $args['type'] = 'hidden';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $boxName = $field->boxName;
        $request = $field->getForm()->context->getRequest();
        switch ($field->varType) {
            case 'bool':
            case 'boolean':
                $field->value = $request->req($data, $boxName . ':b', $field->default);
                break;
            case 'int':
            case 'integer':
                $val = $request->req($data, $boxName . ':s', $field->default);
                if (preg_match('@[+-]?\d*\.\d+@', $field->default)) {
                    $field->value = $request->req($data, $boxName . ':f', $field->default);
                } else {
                    $field->value = $request->req($data, $boxName . ':i', $field->default);
                }
                break;
            case 'double':
            case 'float':
                $field->value = $request->req($data, $boxName . ':f', $field->default);
                break;
            case 'string':
                $field->value = $request->req($data, $boxName . ':s', $field->default);
                break;
            case 'array':
                $field->value = $request->req($data, $boxName . ':a', $field->default);
                break;
            default :
                $field->value = $request->req($data, $boxName, $field->default);
                break;
        }
        return $field->value;
    }

    public function fill(Field $field, array &$values)
    {
        $values[$field->name] = $field->value;
    }

    public function init(Field $field, array $values)
    {
        $field->value = isset($values[$field->name]) ? $values[$field->name] : null;
    }
}