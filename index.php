<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}
require(ROOT_DIR . '/vendor/autoload.php');

use \core\Route;

$a = function () {
};
var_export($a instanceof Closure);

Route::register('home');

Route::run();

