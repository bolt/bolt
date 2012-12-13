<?php

$asynchronous = $app['controllers_factory'];

$asynchronous->get("/dashboardnews", '\Bolt\Controllers\Async::dashboardnews')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('dashboardnews');

$asynchronous->get("/latestactivity", '\Bolt\Controllers\Async::latestactivity')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('latestactivity');

$asynchronous->get("/filesautocomplete", '\Bolt\Controllers\Async::filesautocomplete')
    ->before('\Bolt\Controllers\Async::before');

$asynchronous->get("/readme/{extension}", '\Bolt\Controllers\Async::readme')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('readme');

$asynchronous->get("/widget/{key}", '\Bolt\Controllers\Async::widget')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('widget');

$asynchronous->post("/markdownify", '\Bolt\Controllers\Async::markdownify')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('markdownify');

$asynchronous->get("/makeuri", '\Bolt\Controllers\Async::makeuri')
    ->before('\Bolt\Controllers\Async::before');

$asynchronous->get("/activitylog", '\Bolt\Controllers\Async::activitylog')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('activitylog');

$asynchronous->get("/lastmodified/{contenttypeslug}", '\Bolt\Controllers\Async::lastmodified')
    ->before('\Bolt\Controllers\Async::before')
    ->bind('lastmodified');

$app->mount('/async', $asynchronous);
