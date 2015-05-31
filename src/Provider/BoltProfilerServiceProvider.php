<?php

namespace Bolt\Provider;

use Bolt\DataCollector\BoltDataCollector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class BoltProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // Set the 'bolt' toolbar item as the first one, and overriding the 'Symfony' one.
        // Note: we use this workaround, because setting $app['data_collector.templates'][0]
        // does not work.
        $templates = array_merge(
            [['bolt', '@BoltProfiler/toolbar/bolt.html.twig']],
            $app['data_collector.templates']
        );

        // Hackishly replace the template for the first toolbar item with our own.
        $templates[1][1] = '@BoltProfiler/toolbar/config.html.twig';

        $app['data_collector.templates'] = $templates;

        $app['data_collectors'] = array_merge(
            $app['data_collectors'],
            [
                'bolt' => $app->share(
                    function ($app) {
                        return new BoltDataCollector($app);
                    }
                ),
            ]
        );

        $app['twig.loader.filesystem'] = $app->share(
            $app->extend(
                'twig.loader.filesystem',
                function (\Twig_Loader_Filesystem $filesystem, Application $app) {
                    $filesystem->addPath($app['resources']->getPath('app/view'), 'BoltProfiler');

                    return $filesystem;
                }
            )
        );
    }

    public function boot(Application $app)
    {
    }
}
