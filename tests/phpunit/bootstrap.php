<?php

/*
 * Test bootstrapper. This leaves out all stuff registering services and
 * related to request dispatching.
 */

// Define our install type
if (file_exists(__DIR__ . '/../../../../../vendor/bolt/bolt/')) {
    $installType = 'composer';
} else {
    $installType = 'git';
}

// Install base location
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', realpath(__DIR__ . '/../../'));
}

// PHPUnit's base location
if (!defined('PHPUNIT_ROOT')) {
    define('PHPUNIT_ROOT', realpath(TEST_ROOT . '/tests/phpunit/unit'));
}

if (!defined('BOLT_AUTOLOAD')) {
    if (is_dir(TEST_ROOT . '/../../../vendor/')) {
        // Composer install
        define('BOLT_AUTOLOAD', TEST_ROOT . '/../../autoload.php');
    } else {
        // Git/tarball install
        define('BOLT_AUTOLOAD', TEST_ROOT . '/vendor/autoload.php');
    }

    // Load the autoloader
    require_once BOLT_AUTOLOAD;
}

// Path to Nut
if (!defined('NUT_PATH')) {
    if ($installType === 'composer') {
        define('NUT_PATH', realpath(TEST_ROOT . '/vendor/bolt/bolt/app/nut'));
    } elseif ($installType === 'git') {
        define('NUT_PATH', realpath(TEST_ROOT . '/app/nut'));
    }
}

// Load the upload bootstrap
require_once 'unit/bootstraps/upload-bootstrap.php';
