<?php
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Ensure load.php was called right before bootstrap.php
$includes     = get_included_files();
$loaderPath   = __DIR__ . DIRECTORY_SEPARATOR . 'load.php';
$includeCount = count($includes);
// Should be at least 3 includes at this point:
//   invoker.php (usually entry point), load.php, bootstrap.php
// Second to last entry must be load.php
$isLoadChainOk = $includeCount >= 3 && $includes[$includeCount - 2] == $loaderPath;
if (!$isLoadChainOk) {
    throw new \RuntimeException('Include load.php, not bootstrap.php');
}

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, etc.
require_once __DIR__ . '/classes/lib.php';
require_once __DIR__ . '/src/Bolt/Configuration/LowlevelChecks.php';

$checker = new Bolt\Configuration\LowlevelChecks;
require_once $checker->autoloadCheck(getcwd());


if (strpos(__DIR__, "/vendor/") !== false) {
    $config = new Bolt\Configuration\Composer(getcwd());
} else {
    $config = new Bolt\Configuration\Standard(__DIR__ . "/../");
}
$config->verify();
$config->compat();


// Create the 'Bolt application'
$app = new Bolt\Application(array('resources' => $config));


// Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
$app->initialize();

return $app;
