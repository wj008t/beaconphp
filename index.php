<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}
require(ROOT_DIR . '/vendor/autoload.php');

use \core\Route;
use \core\Field;

Route::register('home');

Route::run();

$form = new \core\Form();

$form->getBoxInstance('hidden');