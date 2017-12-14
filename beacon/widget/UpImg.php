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
        $args['yee-module'] = 'upfile imgshower';

        $field->dataShowType = $field->dataShowType == null ? 1 : $field->dataShowType;
        $field->dataHideInput = $field->dataHideInput == null ? 0 : $field->dataHideInput;
        $field->dataBtnText = $field->dataBtnText == null ? '选择图片' : $field->dataBtnText;
        $field->dataShowMaxwidth = $field->dataShowMaxwidth == null ? 120 : $field->dataShowMaxwidth;
        $field->dataShowMaxheight = $field->dataShowMaxheight == null ? 120 : $field->dataShowMaxheight;

        $field->explodeAttr($attr, $args);
        $field->explodeData($attr);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, string $method = '')
    {
        $field->varType = 'string';
        return parent::assign($field, $method);
    }
}