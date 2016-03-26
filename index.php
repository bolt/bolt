<?php

/*
 * `dirname(__FILE__)` is intentional to support PHP 5.2 until legacy.php can be shown.
 */
/** @var Silex\Application|false $app */
$app = require dirname(__FILE__) . '/app/web.php';

// If web.php returns false, meaning the path is a file, pass it along.
if ($app === false) {
    return false;
}

$app->run();
