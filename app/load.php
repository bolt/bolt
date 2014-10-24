<?php
/**
 * First stage loader
 *
 * Here we get things started by ensuring the PHP version we're running on
 * is supported by the app.  We'll display a friendly error page if not.
 *
 * Next, we'll check if the script is being run with PHP's built in web
 * server, and make sure static assets are handled gracefully.
 *
 * Last but not least, we pass things off to the second stage loader
 */

/**
 * Version must be greater than 5.3.3.
 * See: https://github.com/bolt/bolt/issues/1531
 */
if (version_compare(PHP_VERSION, '5.3.3', '<')) {
    require __DIR__ . '/legacy.php';
    exit;
}

/**
 * Return false if the requested file is available on the filesystem.
 * See: http://silex.sensiolabs.org/doc/web_servers.html#php-5-4
 */
if ('cli-server' == php_sapi_name()) {
    $filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

    if (is_file($filename)) {
        return false;
    }
}

/**
 * Bring in the second stage loader.
 */

return require_once __DIR__ . '/bootstrap.php';
