<?php

namespace Bolt\Provider;

use Bolt\DataCollector\DatabaseDataCollector;
use Doctrine\DBAL\Logging\DebugStack;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DatabaseProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['data_collector.templates'] = array_merge(
            $app['data_collector.templates'],
            array(
                array('db', '@BoltProfiler/toolbar/db.html.twig'),
            )
        );

        $app['data_collectors'] = array_merge(
            $app['data_collectors'],
            array(
                'db' => $app->share(
                    function ($app) {
                        return new DatabaseDataCollector($app['db.logger']);
                    }
                ),
            )
        );

        $app['db.logger'] = $app->share(
            function () {
                return new DebugStack();
            }
        );
    }

    public function boot(Application $app)
    {
        if ($app['debug'] === true) {
            $app['db.config']->setSQLLogger($app['db.logger']);
        }
    }
}
