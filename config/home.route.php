<?php
//路由配置
return [
    'path' => 'app/home',
    'namespace' => 'app\\home',
    'base' => '/',
    'rules' => [
        '@^/(\w+)/(\w+)/(\d+)$@i' => [
            'ctl' => '$1',
            'act' => '$2',
            'id' => '$3',
        ],
        '@^/(\w+)/(\w+)$@i' => [
            'ctl' => '$1',
            'act' => '$2',
        ],
        '@^/(\w+)/?$@i' => [
            'ctl' => '$1',
            'act' => 'index',
        ],
        '@^/$@' => [
            'ctl' => 'index',
            'act' => 'index',
        ],
    ],
    'resolve' => function ($ctl, $act, $keys) {
        $url = '/{ctl}';
        if (!empty($act)) {
            $url .= '/{act}';
        }
        if (isset($keys['id'])) {
            $url .= '/{id}';
        }
        return $url;
    }
];