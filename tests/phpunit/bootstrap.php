<?php

/*
 * Test bootstrapper. This leaves out all stuff registering services and
 * related to request dispatching.
 */

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
    global $CLASSLOADER;
    $CLASSLOADER = require_once BOLT_AUTOLOAD;
}

// Load the upload bootstrap
require_once 'unit/bootstraps/upload-bootstrap.php';
