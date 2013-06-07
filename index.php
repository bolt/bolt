<?php

if (version_compare(PHP_VERSION, '5.3.2') < 0) {
    die("Bolt requires PHP <u>5.3.2</u> or higher. You have PHP <u>" . PHP_VERSION . "</u>, so Bolt will not run on your current setup.");
}

require_once __DIR__.'/app/bootstrap.php';

if ($app['debug']) {
    $app->run();
} else {
    $app['http_cache']->run();
}
