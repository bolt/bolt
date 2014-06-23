<?php

// Check if we're at least on PHP 5.3.3. We do this check here, because as soon as
// we require any other files, we'll get a fatal error, because the parser chokes
// on the backslashes in Namespaces.
if (version_compare(PHP_VERSION, '5.3.3', '<')) {
    require_once dirname(__FILE__) . '/app/legacy.php';
    exit;
}

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
$app->run();

