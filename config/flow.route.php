<?php
//路由配置
return [
    'path' => 'app/flow',
    'namespace' => 'app\\flow',
    'base' => '/flow',
    'rules' => [
        '@^/(main)_(\d+)/(\w+)$@i' => [
            'ctl' => '$1',
            'act' => '$3',
            'fid' => '$2'
        ],
        '@^/(\w+)/(\w+)(\.json)?$@i' => [
            'ctl' => '$1',
            'act' => '$2',
        ],
        '@^/(\w+)/?(\.json)?$@i' => [
            'ctl' => '$1',
            'act' => 'index',
        ],
        '@^/(\.json)?$@' => [
            'ctl' => 'index',
            'act' => 'index',
        ],
    ],
    'resolve' => function ($ctl, $act, $keys) {
        $url = '/{ctl}';
        if (!empty($act)) {
            $url .= '/{act}';
        }
        return $url;
    }
];