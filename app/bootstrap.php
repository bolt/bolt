<?php

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, etc.
require_once __DIR__ . '/classes/lib.php';
require_once __DIR__ . '/src/Bolt/Configuration/LowlevelChecks.php';

$checker = new Bolt\Configuration\LowlevelChecks;
require_once $checker->autoloadCheck(getcwd());


if (strpos(__DIR__, "/vendor/") !== false) {
    $config = new Bolt\Configuration\Composer(getcwd());
} else {
    $config = new Bolt\Configuration\Standard(__DIR__."/../");
}
$config->verify();
$config->compat();


// Create the 'Bolt application'
$app = new Bolt\Application(array('resources' => $config));


// Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
$app->initialize();

return $app;
