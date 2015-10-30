<?php

/*
 * Test bootstrapper. This leaves out all stuff registering services and
 * related to request dispatching.
 */

// Define our install type
if (!defined('INSTALL_TYPE')) {
    if (file_exists(__DIR__ . '/../../../../../vendor/bolt/bolt/')) {
        define('INSTALL_TYPE', 'composer');
    } else {
        define('INSTALL_TYPE', 'git');
    }
}

// Install base location
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', realpath(__DIR__ . '/../../'));
}

// PHPUnit's base location
if (!defined('PHPUNIT_ROOT')) {
    define('PHPUNIT_ROOT', realpath(TEST_ROOT . '/tests/phpunit/unit'));
}

// PHPUnit's temporary web root… It doesn't exist yet, so we can't realpath()
if (!defined('PHPUNIT_WEBROOT')) {
    define('PHPUNIT_WEBROOT', dirname(PHPUNIT_ROOT) . '/web-root');
}

if (!defined('BOLT_AUTOLOAD')) {
    if (INSTALL_TYPE === 'composer') {
        define('BOLT_AUTOLOAD', TEST_ROOT . '/../../autoload.php');
    } else {
        define('BOLT_AUTOLOAD', TEST_ROOT . '/vendor/autoload.php');
    }

    // Load the autoloader
    require_once BOLT_AUTOLOAD;
}

// Path to Nut
if (!defined('NUT_PATH')) {
    define('NUT_PATH', realpath(__DIR__ . '/nutty'));
}

// Load the upload bootstrap
require_once 'unit/bootstraps/upload-bootstrap.php';
