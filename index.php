<?php
/**
 * Bolt entry script
 *
 * Here we'll require in the first stage load script, which handles all the
 * tasks needed to return the app.  Once we get the app, we simply tell it
 * to run, building a beautiful web page for you and other visitors.
 *
 * We get things started by ensuring the PHP version we're running on
 * is supported by the app.  We'll display a friendly error page if not.
 *
 * Next, we'll check if the script is being run with PHP's built in web
 * server, and make sure static assets are handled gracefully.
 *
 */

/**
 * Version must be greater than 5.3.3.
 *
 * Note, we use `dirname(__FILE__)` instead of `__DIR__`. The latter was introduced "only" in
 * PHP 5.3, and we need to be able to show the notice to the poor souls who are still on PHP 5.2.
 *
 * @see: https://github.com/bolt/bolt/issues/1531
 * @see: https://github.com/bolt/bolt/issues/3371
 */
if (version_compare(PHP_VERSION, '5.3.3', '<')) {
    require dirname(__FILE__) . '/app/legacy.php';
    return false;
}

/**
 * Return false if the requested file is available on the filesystem.
 * @see: http://silex.sensiolabs.org/doc/web_servers.html#php-5-4
 */
if (php_sapi_name() == 'cli-server') {
    $filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

    if (is_file($filename)) {
        return false;
    }
}

/**
 * @var \Bolt\Application $app
 */
$app = require_once __DIR__ . '/app/bootstrap.php';

if ($app) {
    $app->run();
} else {
    return false;
}
