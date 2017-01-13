<?php

namespace Bolt\Provider;

use Bolt\Debug\ShutdownHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\EventListener\DebugHandlersListener;

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

        $app['debug.debug_handlers_listener'] = $app->share(
            function ($app) {
                return new DebugHandlersListener(
                    null,
                    $app['logger'],
                    ShutdownHandler::$errorLevels, // Levels
                    ShutdownHandler::$errorLevels, // Throw at
                    true, // Scream
                    $app['debug.file_link_formatter']
                );
            }
        );

        $app['debug.file_link_formatter'] = null;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        if (!$app['debug']) {
            ShutdownHandler::register(false);
        } else {
            $app['dispatcher']->addSubscriber($app['debug.debug_handlers_listener']);
        }

        $errorLevel = $app['config']->get($app['debug'] ? 'general/debug_error_level' : 'production_error_level');
        if ($errorLevel !== null) {
            error_reporting($errorLevel);
        }
    }
}
