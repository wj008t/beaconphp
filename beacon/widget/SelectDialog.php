<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/16
 * Time: 2:04
 */

namespace widget;


use beacon\Field;

class SelectDialog extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'select_dialog';
        $args['type'] = 'text';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

}