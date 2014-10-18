<?php
/**
 * Bolt entry script
 *
 * Here we'll require in the first stage load script, which handles all the
 * tasks needed to return the app.  Once we get the app, we simply tell it
 * to run, building a beautiful web page for you and other visitors.
 */

/**
 * @var \Bolt\Application $app
 */
$app = require_once __DIR__ . '/app/load.php';

$app->run();
