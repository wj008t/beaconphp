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
        $field->explodeData($attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, string $method = '')
    {
        $boxName = $field->boxName;
        $request = Request::instance();
        if ($method == 'get') {
            $func = new \ReflectionMethod($request, 'get');
        } elseif ($method == 'post') {
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