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

class DateTime extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'datetime';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, string $method = '')
    {
        $field->varType = 'string';
        parent::assign($field, $method);
        if (!Validate::test_date($field->value)) {
            $field->value = null;
        }
    }

}