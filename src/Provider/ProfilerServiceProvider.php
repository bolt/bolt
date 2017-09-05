<?php

namespace Bolt\Provider;

use Bolt\Profiler\BoltDataCollector;
use Bolt\Profiler\DatabaseDataCollector;
use Bolt\Profiler\RequestMatcher;
use Doctrine\DBAL\Logging\DebugStack;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class ProfilerServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        if (!isset($app['profiler'])) {
            $app->register(new WebProfilerServiceProvider());
        }

        $app['profiler.request_matcher'] = function ($app) {
            return new RequestMatcher($app['config'], $app['session'], $app['access_control']);
        };

        $app['profiler.cache_dir'] = function ($app) {
            return $app['path_resolver']->resolve('%cache%/profiler');
        };

        $app['data_collector.templates'] = $app->extend(
            'data_collector.templates',
            function ($templates) {
                // Set the 'bolt' toolbar item as the first one, and overriding the 'Symfony' one.
                array_unshift($templates, ['bolt', '@BoltProfiler/bolt.html.twig']);

                $templates[] = ['db', '@BoltProfiler/db.html.twig'];

                // Hackishly replace the template for the first toolbar item with our own.
                $templates[1][1] = '@BoltProfiler/config.html.twig';

                return $templates;
            }
        );

        $app['data_collectors'] = $app->extend(
            'data_collectors',
            function ($collectors, $app) {
                // @codingStandardsIgnoreStart
                $collectors['bolt'] = function ($app) { return new BoltDataCollector($app); };
                $collectors['db'] = function ($app) { return new DatabaseDataCollector($app['db.logger']); };
                // @codingStandardsIgnoreEnd

                return $collectors;
            }
        );

        $app['twig.loader.bolt_filesystem'] = $app->extend(
            'twig.loader.bolt_filesystem',
            function ($filesystem) {
                $filesystem->addPath('bolt://templates/toolbar', 'BoltProfiler');

                return $filesystem;
            }
        );

        $app['db.logger'] = function () {
            return new DebugStack();
        };

        $app['editlink'] = null;
        $app['edittitle'] = null;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $app->before(function (Request $request, Application $app) {
            $request->attributes->set('_auththoken_name', $app['token.authentication.name']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        if ($app['debug'] === true) {
            $app['db.config']->setSQLLogger($app['db.logger']);
        }
    }
}
