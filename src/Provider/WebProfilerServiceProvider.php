<?php

namespace Bolt\Provider;

use Silex\Application;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;

/**
 * Only use TraceableEventDispatcher and Profiler if $app['debug'] is true.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class WebProfilerServiceProvider extends \Silex\Provider\WebProfilerServiceProvider
{
    public function register(Application $app)
    {
        // Store previous dispatcher.
        $dispatcherFactory = $app->raw('dispatcher');

        parent::register($app);

        // Revert change made to wrap dispatcher in TraceableEventDispatcher
        $app['dispatcher'] = $dispatcherFactory;

        // Try again, but only if app['debug'] is true.
        $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, $app) {
            if (!$app['debug']) {
                return $dispatcher;
            }

            return new TraceableEventDispatcher($dispatcher, $app['stopwatch'], $app['logger']);
        }));

        $app['profiler'] = $app->share($app->extend('profiler', function ($profiler, $app) {
            if (!$app['debug']) {
                $profiler->disable();
            }

            return $profiler;
        }));
    }
}
