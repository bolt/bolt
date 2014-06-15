<?php

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
if(strpos(__DIR__, "/vendor/") !== false) {
    $autoload = __DIR__ . '/../../../../vendor/autoload.php';
    $composer = true;
} else {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    $composer = false;
}

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, if the minimum PHP version is present, etc.
require_once __DIR__ . '/classes/lib.php';
require_once __DIR__ . '/classes/util.php';
require_once __DIR__ . '/src/Bolt/Configuration/LowlevelChecks.php';

if(!require_once($autoload)) {
    $checker = new Bolt\Configuration\LowlevelChecks;
    $checker->lowlevelError("The file <code>vendor/autoload.php</code> doesn't exist. Make sure " .
                "you've installed the required components with Composer.");
}
if($composer) {
    $config = new Bolt\Configuration\ComposerResources(__DIR__."/../");
} else {
    $config = new Bolt\Configuration\ResourceManager(__DIR__."/../");
}

$config->compat();
$config->verify();


// Create the 'Bolt application'
$app = new Bolt\Application(array('resources'=>$config));

// Finally, check if the app/database folder is writable, if it needs to be.
$checker = new Bolt\Configuration\LowlevelChecks($config);
$checker->doDatabaseCheck($app['config']);

// Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
$app->initialize();
