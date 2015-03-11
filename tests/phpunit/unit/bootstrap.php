<?php

/*
 * Test bootstrapper. This leaves out all stuff registering services and
 * related to request dispatching.
 */
global $CLASSLOADER;

// Install base location
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', realpath(__DIR__ . '/../../../'));
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
    $CLASSLOADER = require_once BOLT_AUTOLOAD;
}

// Load the upload bootstrap
require_once 'bootstraps/upload-bootstrap.php';

// Make sure we wipe the db file to start with a clean one
if (is_readable(TEST_ROOT . '/bolt.db')) {
    unlink(TEST_ROOT . '/bolt.db');
}
copy(PHPUNIT_ROOT . '/resources/db/bolt.db', TEST_ROOT . '/bolt.db');

@mkdir(TEST_ROOT . '/app/cache/', 0777, true);
