<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}
require(ROOT_DIR . '/vendor/autoload.php');

use \core\Route;

Route::register('home');

Route::run();


