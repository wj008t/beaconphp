<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 0:52
 */

namespace app\home\form;

use core\DB;
use core\Form;

class UserForm extends Form
{
    protected function load()
    {
        return [
            'name' => [
                'label' => '标题',
                'default' => function () {
                    return DB::getMax('@pf_advertisement', 'name');
                }
            ],
            'sex' => [
                'label' => '性别',
                'type' => 'text',
            ]
        ];
    }
}