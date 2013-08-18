<?php

// If you wish to put the folder with the Bolt configuration files in another location than /app/config/,
// define it here as a constant. If you do not define it here, the default location is used.

// One level above the 'webroot'
// define('BOLT_CONFIG_DIR', dirname(__DIR__) . '/config');

if (version_compare(PHP_VERSION, '5.3.2') < 0) {
    die("Bolt requires PHP <u>5.3.2</u> or higher. You have PHP <u>" . PHP_VERSION . "</u>, so Bolt will not run on your current setup.");
}

require_once __DIR__.'/app/bootstrap.php';

if ($app['debug']) {
    $app->run();
} else {
    $app['http_cache']->run();
}
