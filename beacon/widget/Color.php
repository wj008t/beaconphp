<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/16
 * Time: 2:01
 */

namespace widget;


use beacon\Field;

class Color extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'color';
        $args['type'] = 'hidden';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'string';
        return parent::assign($field, $data);
    }
}