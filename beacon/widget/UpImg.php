<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace widget;


use beacon\Field;

class UpImg extends Hidden
{

    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'upimage';
        $field->dataBtnWidth = $field->dataBtnWidth == null ? 150 : $field->dataBtnWidth;
        $field->dataBtnHeight = $field->dataBtnHeight == null ? 100 : $field->dataBtnHeight;
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