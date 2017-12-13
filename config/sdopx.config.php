<?php
//sdopx模板引擎配置项
return [
    //模板路径
    'template_dir' => '/view',
    //公共模板路径
    'common_dir' => '/view/common',
    //是否强制编译
    'force_compile' => false,
    //是否检查编译
    'compile_check' => true,
    //编译时紧密程度 0 1 2 3 4
    'compile_format' => 0,
    //默认模板后缀名
    'extension' => '.tpl',
    //插件包资源配置
    'plugins_dir' => [
        '/libs/sdopx/plugins'
    ],
];
