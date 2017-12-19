<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 3:36
 */

namespace widget;


use beacon\Field;

class Check extends Hidden
{

    public function code(Field $field, $args)
    {
        $args['checked'] = $field->value ? 'checked' : '';
        $args['type'] = 'checkbox';
        $args['value'] = 1;
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'boolean';
        return parent::assign($field, $data);
    }

    public function fill(Field $field, array &$values)
    {
        $values[$field->name] = $field->value ? 1 : 0;
    }

    public function init(Field $field, array $values)
    {
        $field->value = isset($values[$field->name]) ? ($values[$field->name] == 1) : null;
    }
}