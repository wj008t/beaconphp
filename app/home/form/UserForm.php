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
            'integer' => [
                'label' => '整数',
                'type' => 'integer',
            ],
            'number' => [
                'label' => '数值',
                'type' => 'number',
            ],
            'text' => [
                'label' => '文本框',
                'type' => 'text',
            ],

            'radio' => [
                'label' => '单选框',
                'type' => 'radio',
            ],

            'select1' => [
                'label' => '下拉框',
                'type' => 'select',
                'header' => ['text' => '请选择', 'value' => 0],
                'options' => [
                    ['value' => 1, 'text' => '选项1'],
                    ['value' => 2, 'text' => '选项2'],
                    ['value' => 3, 'text' => '选项3'],
                ]
            ],

            'select2' => [
                'label' => '下拉框',
                'type' => 'select',
                'header' => '请选择',
                'var-type' => 'int',
                'options' => [
                    ['value' => 1, 'text' => '选项1', 'tips' => '测试提示用的'],
                    ['value' => 2, 'text' => '选项2'],
                    ['value' => 3, 'text' => '选项3'],
                    ['text' => '选项3',
                        'group' => [
                            ['value' => 11, 'text' => '选项11'],
                            ['value' => 22, 'text' => '选项22', 'tips' => '测试提示用的'],
                            ['value' => 33, 'text' => '选项33'],
                        ]
                    ],
                    ['value' => 4, 'text' => '选项4'],
                ]
            ],

            'pwd' => [
                'label' => '密码框',
                'type' => 'password',
                'encode-func' => 'md5'
            ],

            'textarea' => [
                'label' => '备注框',
                'type' => 'textarea',
            ],

            'upfile' => [
                'label' => '上传框',
                'type' => 'up_file',
            ],

            'upimg' => [
                'label' => '上传图片',
                'type' => 'up_img',
            ],

            'xheditor' => [
                'label' => '文本编辑器',
                'type' => 'xh_editor',
            ],
        ];
    }
}