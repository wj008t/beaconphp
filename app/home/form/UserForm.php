<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 0:52
 */

namespace app\home\form;

use core\Form;

class UserForm extends Form
{
    public function load()
    {
        return [
            'name' => [
                'label' => '标题',

            ],
            'sex' => [
                'label' => '性别',
                'type' => 'text',
            ]
        ];
    }
}