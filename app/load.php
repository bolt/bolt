<?php
// Very low level runtime environment checks
// See: https://github.com/bolt/bolt/issues/1531

// Check if we're at least on PHP 5.3.3. We do this check here, because as soon as
// we require any other files, we'll get a fatal error, because the parser chokes
// on the backslashes in Namespaces.
if (version_compare(PHP_VERSION, '5.3.3', '<')) {
    require __DIR__ . '/legacy.php';
    exit;
}

// PHP -S (built-in webserver) doesn't handle static assets without a `return false`
// For more information, see: http://silex.sensiolabs.org/doc/web_servers.html#php-5-4
if ('cli-server' == php_sapi_name()) {
    $filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

    // If it is a file, just return false.
    if (is_file($filename)) {
        return false;
    }
}

// Invoke the second-stage loader and pass back the application instance it returned
return require_once __DIR__ . '/bootstrap.php';
