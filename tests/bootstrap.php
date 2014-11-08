<?php

/*
 * Test bootstrapper. This leaves out all stuff registering services and
 * related to request dispatching.
 */
global $CLASSLOADER;

if (is_dir(__DIR__ . '/../../../../vendor/')) {
    $CLASSLOADER = require_once __DIR__ . '/../../../autoload.php';
} else {
    $CLASSLOADER = require_once __DIR__ . '/../vendor/autoload.php';
}

require_once 'upload-bootstrap.php';
require_once 'lowlevel-bootstrap.php';

if(!defined('TEST_ROOT')) {
    define('TEST_ROOT', realpath(__DIR__ . '/../'));
}
