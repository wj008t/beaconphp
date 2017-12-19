<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 17:34
 */

namespace widget;

use beacon\Field;

interface BoxInterface
{
    public function code(Field $field, $args);

    public function assign(Field $field, array $data);

    public function fill(Field $field, array &$values);

    public function init(Field $field, array $values);
}