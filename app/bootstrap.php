<?php

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, if the minimum PHP version is present, etc.
require_once __DIR__ . '/classes/lib.php';
require_once __DIR__ . '/classes/util.php';
require_once __DIR__ . '/src/Bolt/Configuration/LowlevelChecks.php';

$autoload = array(
  'standard' =>  __DIR__ . '/../vendor/autoload.php',
  'composer'=>   __DIR__ . '/../../../../vendor/autoload.php'
);

foreach($autoload as $type=>$path) {
   if(require_once $path) {
    $install = $type;
   } 
}

if(!$install) {
    $checker = new Bolt\Configuration\LowlevelChecks;
    $checker->lowlevelError("The file <code>vendor/autoload.php</code> doesn't exist. Make sure " .
                "you've installed the required components with Composer.");
}
if($install == 'composer') {
    $config = new Bolt\Configuration\Composer(__DIR__."/../");
} else {
    $config = new Bolt\Configuration\Standard(__DIR__."/../");
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
