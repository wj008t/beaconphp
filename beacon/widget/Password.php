<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace widget;


use beacon\Field;

class Password extends Hidden
{

    public function code(Field $field, $args)
    {
        $args['type'] = 'password';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        return '<input ' . join(' ', $attr) . ' />';
    }

    public function assign(Field $field, array $data)
    {
        $field->varType = 'string';
        return parent::assign($field, $data);
    }

    public function fill(Field $field, array &$values)
    {
        if ($field->value !== null && $field->value !== '' && $field->encodeValue !== $field->value && $field->encodeFunc !== null) {
            if (is_callable($field->encodeFunc)) {
                $field->encodeValue = call_user_func($field->encodeFunc, $field->value);
                $values[$field->name] = $field->encodeValue;
                return;
            }
        }
        $values[$field->name] = $field->value;
    }

    public function init(Field $field, array $values)
    {
        $field->value = isset($values[$field->name]) ? $values[$field->name] : null;
        $field->encodeValue = $field->value;
    }

}