<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 4:01
 */

namespace widget;


use beacon\Field;
use beacon\Validate;

class Integer extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'integer';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'int';
        return parent::assign($field, $data);
    }

}