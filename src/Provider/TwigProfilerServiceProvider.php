<?php

namespace Bolt\Provider;

use Bolt\DataCollector\TwigDataCollector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TwigProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['data_collector.templates'] = array_merge(
            $app['data_collector.templates'],
            array(
                array('twig', '@BoltProfiler/toolbar/twig.html.twig'),
            )
        );

        $app['data_collectors'] = array_merge(
            $app['data_collectors'],
            array(
                'twig' => $app->share(
                    function ($app) {
                        return $app['twig.logger'];
                    }
                ),
            )
        );

        $app['twig.logger'] = $app->share(
            function ($app) {
                return new TwigDataCollector($app);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
