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
                    //  return DB::getMax('@pf_advertisement', 'name');
                    return 'test';
                }
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
            'text' => [
                'label' => '文本框',
                'type' => 'text',
            ],

            'check' => [
                'label' => '单选框',
                'type' => 'check',
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


            'radio_group' => [
                'label' => '单选框',
                'type' => 'radio_group',
                'data-val' => ['r' => true],
                'default' => 3,
                'options' => [
                    ['value' => 1, 'text' => '选项1'],
                    ['value' => 2, 'text' => '选项2'],
                    ['value' => 3, 'text' => '选项3'],
                ]
            ],


            'check_group' => [
                'label' => '多选框',
                'type' => 'check_group',
                'data-val' => ['r' => true],
                'default' => 3,
                'options' => [
                    ['value' => 1, 'text' => '选项1'],
                    ['value' => 2, 'text' => '选项2'],
                    ['value' => 3, 'text' => '选项3'],
                ],
                'names' => ['check_name1', 'check_name2', 'check_name3'],
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

            'select_dialog' => [
                'label' => '选择对话框',
                'type' => 'select_dialog',
                'data-href' => 'http://www.beacon.com/',
            ],

            'linkage' => [
                'label' => '关联',
                'type' => 'linkage',
                'names' => ['name1', 'name2', 'name3'],
                'varType' => 'int',
                'value' => [11, 21, 32],
                'data-source' => [
                    [
                        'text' => '1选项1',
                        'value' => '11',
                        'childs' => [
                            [
                                'text' => '2选项1',
                                'value' => '21',
                                'childs' => [
                                    [
                                        'text' => '3选项1',
                                        'value' => '31',
                                    ],
                                    [
                                        'text' => '3选项2',
                                        'value' => '32',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

        ];
    }
}