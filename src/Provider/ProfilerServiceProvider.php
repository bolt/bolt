<?php

namespace Bolt\Provider;

use Bolt\Profiler\BoltDataCollector;
use Bolt\Profiler\DatabaseDataCollector;
use Bolt\Profiler\DebugToolbarEnabler;
use Doctrine\DBAL\Logging\DebugStack;
use Silex;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Twig_Loader_Filesystem;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class ProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['profiler'])) {
            $app->register(
                new Silex\Provider\WebProfilerServiceProvider(),
                [
                    'profiler.cache_dir'                => $app['resources']->getPath('cache/profiler'),
                    'web_profiler.debug_toolbar.enable' => false, // We enable it below
                ]
            );
        }

        $app->register(new DebugToolbarEnabler());

        $app['data_collector.templates'] = $app->share(
            $app->extend(
                'data_collector.templates',
                function ($templates) {
                    // Set the 'bolt' toolbar item as the first one, and overriding the 'Symfony' one.
                    array_unshift($templates, ['bolt', '@BoltProfiler/bolt.html.twig']);

                    $templates[] = ['db', '@BoltProfiler/db.html.twig'];

                    // Hackishly replace the template for the first toolbar item with our own.
                    $templates[1][1] = '@BoltProfiler/config.html.twig';

                    return $templates;
                }
            )
        );

        $app['data_collectors'] = $app->share(
            $app->extend(
                'data_collectors',
                function ($collectors, $app) {
                    // @codingStandardsIgnoreStart
                    $collectors['bolt'] = $app->share(function ($app) { return new BoltDataCollector($app); });
                    $collectors['db'] = $app->share(function ($app) { return new DatabaseDataCollector($app['db.logger']); });
                    // @codingStandardsIgnoreEnd

                    return $collectors;
                }
            )
        );

        $app['twig.loader.filesystem'] = $app->share(
            $app->extend(
                'twig.loader.filesystem',
                function (Twig_Loader_Filesystem $filesystem, $app) {
                    $filesystem->addPath($app['resources']->getPath('app/view/toolbar'), 'BoltProfiler');

                    return $filesystem;
                }
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
