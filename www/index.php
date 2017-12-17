<?php

###一定必须要定义根目录

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
require(ROOT_DIR . '/vendor/autoload.php');

use \beacon\Route;

Route::register('home');
Route::register('service');
Route::run();

