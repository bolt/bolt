<?php

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');


// This seems to be flawed..
//$rootDirectory        = dirname(__DIR__);
//$installedViaComposer = file_exists($rootDirectory . DIRECTORY_SEPARATOR . 'composer.lock');

// We assume that if '/vendor/'. is in the path, it's installed via composer. Needs confirmation..
$installedViaComposer = (strpos(__DIR__, "/vendor/") !== false);

defined('BOLT_COMPOSER_INSTALLED') or define('BOLT_COMPOSER_INSTALLED', $installedViaComposer);

if (BOLT_COMPOSER_INSTALLED) {
    defined('BOLT_PROJECT_ROOT_DIR') or define('BOLT_PROJECT_ROOT_DIR', substr(__DIR__, 0, -21));
    defined('BOLT_WEB_DIR') or define('BOLT_WEB_DIR', BOLT_PROJECT_ROOT_DIR . '/web');
    defined('BOLT_CACHE_DIR') or define('BOLT_CACHE_DIR', BOLT_PROJECT_ROOT_DIR . '/cache');
    defined('BOLT_CONFIG_DIR') or define('BOLT_CONFIG_DIR', BOLT_PROJECT_ROOT_DIR . '/config');
} else {
    defined('BOLT_PROJECT_ROOT_DIR') or define('BOLT_PROJECT_ROOT_DIR', dirname(__DIR__));
    defined('BOLT_WEB_DIR') or define('BOLT_WEB_DIR', BOLT_PROJECT_ROOT_DIR);
    defined('BOLT_CACHE_DIR') or define('BOLT_CACHE_DIR', BOLT_PROJECT_ROOT_DIR . '/app/cache');

    // Set the config folder location. If we haven't set the constant in index.php, use one of the
    // default values.
    if (!defined('BOLT_CONFIG_DIR')) {
        if (is_dir(__DIR__ . '/config')) {
            // Default value, /app/config/..
            define('BOLT_CONFIG_DIR', __DIR__ . '/config');
        } else {
            // otherwise use /config, outside of the webroot folder.
            define('BOLT_CONFIG_DIR', dirname(dirname(__DIR__)) . '/config');
        }
    }
}

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, if the minimum PHP version is present, etc.
require_once __DIR__ . '/classes/lib.php';
require_once __DIR__ . '/classes/lowlevelchecks.php';

$checker = new LowlevelChecks();
$checker->doChecks();

// Let's get on with the rest..
require_once BOLT_PROJECT_ROOT_DIR . '/vendor/autoload.php';
require_once __DIR__ . '/classes/util.php';

// Create the 'Bolt application'.
$app = new Bolt\Application();

// Finally, check if the app/database folder is writable, if it needs to be.
$checker->doDatabaseCheck($app['config']);

// Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
$app->initialize();
