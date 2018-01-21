<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace widget;


use beacon\Field;

class Line implements BoxInterface
{
    public function code(Field $field, $args)
    {
        return '';
    }

    public function assign(Field $field, array $data)
    {

    }

    public function fill(Field $field, array &$values)
    {

    }

    public function init(Field $field, array $values)
    {

    }
}