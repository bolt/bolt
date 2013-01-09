<?php

$app->match("/", 'Bolt\Controllers\Frontend::homepage')
    ->before('Bolt\Controllers\Frontend::before')
    ->bind('homepage');

$app->match('/search', 'Bolt\Controllers\Frontend::search')
    ->before('Bolt\Controllers\Frontend::before');

$app->match('/{contenttypeslug}/feed.{extension}', 'Bolt\Controllers\Frontend::feed')
    ->assert('extension', '(xml|rss)')
    ->before('Bolt\Controllers\Frontend::before')
    ->assert('contenttypeslug', $app['storage']->getContentTypeAssert());

$app->match('/{contenttypeslug}/{slug}', 'Bolt\Controllers\Frontend::record')
    ->before('Bolt\Controllers\Frontend::before')
    ->assert('contenttypeslug', $app['storage']->getContentTypeAssert(true))
    ->bind('contentlink');

$app->match('/{contenttypeslug}', 'Bolt\Controllers\Frontend::listing')
    ->before('Bolt\Controllers\Frontend::before')
    ->assert('contenttypeslug', $app['storage']->getContentTypeAssert());
