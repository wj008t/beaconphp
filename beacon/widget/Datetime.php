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

class Datetime extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'date';
        $args['data-type'] = 'datetime';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'string';
        parent::assign($field, $data);
        if (!Validate::test_date($field->value)) {
            $field->value = null;
        }
    }

}