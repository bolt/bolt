<?php
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;


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

require_once 'bootstraps/upload-bootstrap.php';
require_once 'bootstraps/lowlevel-bootstrap.php';

if(!defined('TEST_ROOT')) {
    define('TEST_ROOT', realpath(__DIR__ . '/../'));
}

// Make sure we wipe the db file to start with a clean one
if(is_readable(TEST_ROOT.'/bolt.db')) {
    unlink(TEST_ROOT.'/bolt.db');
}
@mkdir(__DIR__.'/../app/cache/', 0777, true);



VarDumper::setHandler(function($var) {
    $cloner = new VarCloner();
    $dumper = CliDumper();

    $dumper->dump($cloner->cloneVar($var));
});