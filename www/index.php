<?php

###一定必须要定义根目录

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
require(ROOT_DIR . '/vendor/autoload.php');

use \beacon\Route;

Route::register('home');
Route::register('service');
Route::register('flow');
Route::register('admin');
$url = $_SERVER['PATH_INFO'];
$starttime = microtime(true);
Route::run($url);
echo $url, (microtime(true) - $starttime) * 1000, "\n";
