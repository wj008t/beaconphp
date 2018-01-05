<?php

###一定必须要定义根目录

define('DEV_DEBUG', true);
define('ROOT_DIR', dirname(__DIR__));

require(ROOT_DIR . '/vendor/autoload.php');

use \beacon\Route;

Route::register('home');
Route::register('service');
Route::register('flow');
Route::register('admin');
Route::run();


