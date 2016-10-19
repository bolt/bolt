<?php

namespace Bolt\Provider;

use Bolt\Debug\ShutdownHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DebugServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $previousDebug = $app->raw('debug');
        $app['debug'] = $app->share(function ($app) use ($previousDebug) {
            if (($debugOverride = $app['config']->get('general/debug')) !== null) {
                return $debugOverride;
            }

            return $previousDebug;
        });

        if (!isset($app['environment'])) {
            $app['environment'] = $app->share(function ($app) {
                return $app['debug'] ? 'development' : 'production';
            });
        }

        // Register PHP shutdown functions to catch fatal errors & exceptions
        ShutdownHandler::register();
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        if (!$app['debug']) {
            ShutdownHandler::register(false);
        }

        $errorLevel = $app['config']->get($app['debug'] ? 'general/debug_error_level' : 'production_error_level');
        if ($errorLevel !== null) {
            error_reporting($errorLevel);
        }
    }
}
