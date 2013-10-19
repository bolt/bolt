<?php

namespace Bolt\Provider;

use Doctrine\DBAL\Logging\DebugStack;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Database\BoltDataCollector;

class BoltProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['data_collector.templates'] = array_merge(array(
            array('bolt', '@BoltProfiler/toolbar/bolt.html.twig'),
        ), $app['data_collector.templates']);

        $app['data_collectors'] = array_merge($app['data_collectors'], array(
            'bolt' => $app->share(function ($app) {
                return new BoltDataCollector($app);
            }),
        ));


    }

    public function boot(Application $app)
    {

    }
}
