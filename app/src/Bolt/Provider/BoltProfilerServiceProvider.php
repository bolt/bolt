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

        // Set the 'bolt' toolbar item as the first one, overriding the 'Symfony' one.
        // Note: we use this workartound, because setting $app['data_collector.templates'][0]
        // does not work.
        $templates = $app['data_collector.templates'];
        $templates[0] = array('bolt', '@BoltProfiler/toolbar/bolt.html.twig');
        $app['data_collector.templates'] = $templates;

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
