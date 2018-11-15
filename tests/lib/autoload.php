<?php

define('ROOT', dirname(dirname(__DIR__)));

// Autoload required classes
if (file_exists(ROOT . '/vendor/autoload.php')) {
  require ROOT . "/vendor/autoload.php";
} else {
  require ROOT . "/../../autoload.php";
}
require_once ROOT . '/lib/rule.php';
require_once ROOT . '/lib/parser.php';

spl_autoload_register(function ($className) {
    $filePath = __DIR__ . '/' . $className . '.php';
    if (file_exists($filePath)) {
        include $filePath;
    }
});
