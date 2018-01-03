<?php
//sdopx模板引擎配置项
return [
    //模板路径
    'template_dir' => '/view',
    //公共模板路径
    'common_dir' => '/view/common',
    //编译目录
    'runtime_dir' => '/runtime',
    //是否强制编译
    'compile_force' => false,
    //是否检查编译
    'compile_check' => true,
    //默认模板后缀名
    'extension' => '.tpl',
    'debug' => true,
    //插件包资源配置
    'plugin_dir' => [
        '/libs/plugin'
    ],
];
