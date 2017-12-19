<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 0:52
 */

namespace app\home\form;

use beacon\DB;
use beacon\Form;

class TestForm extends Form
{
    protected function load()
    {
        return [
            'name' => [
                'label' => '标题',
                'default' => function () {
                    //  return DB::getMax('@pf_advertisement', 'name');
                    return 'test';
                },
                'data-val' => ['r' => true],
                'data-val-msg' => ['r' => '标题不能为空'],
            ],
            'integer' => [
                'label' => '整数',
                'type' => 'integer',
            ],
            'date' => [
                'label' => '日期',
                'type' => 'date',
            ],
            'datetime' => [
                'label' => '时间',
                'type' => 'datetime',
            ],
            'number' => [
                'label' => '数值',
                'type' => 'number',
            ],

        ];
    }
}