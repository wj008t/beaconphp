<?php

###一定必须要定义根目录
define('HTTP_SWOOLE', true);

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
require(ROOT_DIR . '/vendor/autoload.php');

use \beacon\Route;

Route::register('home');
Route::register('service');
Route::register('flow');
Route::register('admin');

$http = new swoole_http_server("127.0.0.1", 9501);
$http->on('request', function ($request, $response) {
    $url = $request->server['request_uri'];
    if (Route::runStatic($url, $request, $response, ['static/', 'assets/', 'upfiles/'])) {
        return;
    }
    Route::run($url, $request, $response);
});
$http->start();


