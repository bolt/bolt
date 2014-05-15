<?php

// If you wish to put the folder with the Bolt configuration files in another location than /app/config/,
// define it here as a constant. If you do not define it here, the default location is used.

// One level above the 'webroot'
// define('BOLT_CONFIG_DIR', __DIR__ . '/config');
// define('BOLT_CACHE_DIR', __DIR__ . '/cache');

// PHP -S (built-in webserver) doesn't handle static assets without a `return false`
// For more information, see: http://silex.sensiolabs.org/doc/web_servers.html#php-5-4
if ('cli-server' === php_sapi_name()) {
    $filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

    // If it is a file, just return false.
    if (is_file($filename)) {
        return false;
    }
}
define('BOLT_PROJECT_ROOT_DIR', __DIR__);

require_once __DIR__ . '/app/bootstrap.php';

if (preg_match("^thumbs/[0-9]+x[0-9]+[a-z]*/.*^i", $_SERVER['REQUEST_URI'])) {
    // If it's not a prebuilt file, but it is a thumb that needs processing
    require __DIR__ . '/app/classes/timthumb.php';
} else {
    // Here we go!
    $app->run();
}

