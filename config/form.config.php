<?php

return [
    //字段默认值
    'field_default' => [
        'box-class' => function ($field) {
            if ($field->type == 'check' || $field->type == 'check_group' || $field->type == 'radio_group') {
                return $field->type;
            }
            if ($field->type == 'hidden') {
                return '';
            }
            return 'form-inp ' . $field->type;
        }
    ],
    //验证器的默认提示消息
    'validate_default_errors' => [
        'required' => '必选字段',
        'email' => '请输入正确格式的电子邮件',
        'url' => '请输入正确格式的网址',
        'date' => '请输入正确格式的日期',
        'number' => '仅可输入数字',
        'mobile' => '手机号码格式不正确',
        'idcard' => '身份证号码格式不正确',
        'integer' => '只能输入整数',
        'equalto' => '请再次输入相同的值',
        'equal' => '请输入{0}字符',
        'notequal' => '数值不能是{0}字符',
        'maxlength' => '请输入一个 长度最多是 {0} 的字符串',
        'minlength' => '请输入一个 长度最少是 {0} 的字符串',
        'rangelength' => '请输入 一个长度介于 {0} 和 {1} 之间的字符串',
        'range' => '请输入一个介于 {0} 和 {1} 之间的值',
        'max' => '请输入一个最大为{0} 的值',
        'min' => '请输入一个最小为{0} 的值',
        'remote' => '检测数据不符合要求！',
        'regex' => '请输入正确格式字符',
        'user' => '请使用英文之母开头的字母下划线数字组合',
        'validcode' => '验证码不正确！',
    ]
];