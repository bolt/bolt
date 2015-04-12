<?php
/**
 * Bolt entry script
 *
 * Here we'll require in the first stage load script, which handles all the
 * tasks needed to return the app.  Once we get the app, we simply tell it
 * to run, building a beautiful web page for you and other visitors.
 */

/**
 * Note, we use `dirname(__FILE__)` instead of `__DIR__`. The latter was introduced "only" in
 * PHP 5.3, and we need to be able to show the notice to the poor souls who are still on PHP 5.2.
 *
 * @var \Bolt\Application $app
 */
$app = require_once dirname(__FILE__) . '/app/load.php';

if ($app) {
    $app->run();
} else {
    return false;
}
