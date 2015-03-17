<?php
// This is global bootstrap for autoloading

include_once __DIR__ . '/extensions/CleanupExtension.php';

// // Create a constant that defines the Codeception location
if (!defined('CODECEPTION_ROOT')) {
    define('CODECEPTION_ROOT', realpath(__DIR__));
}

// Create a constant that defines the root location
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', realpath(__DIR__ . '/../../'));
}

// Path to Nut
if (!defined('NUT_PATH')) {
    if (file_exists(__DIR__ . '/../../app/nut')) {
        define('NUT_PATH', realpath(__DIR__ . '/../../app/nut'));
    } else {
        define('NUT_PATH', realpath(__DIR__ . '/../../bolt/bolt/app/nut'));
    }
}
