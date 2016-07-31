<?php

namespace Bolt\Provider;

use Bolt\Configuration\Validation;
use Bolt\EventListener as Listener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Boot service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BootServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['boot.validator'] = $app->share(
            function ($app) {
                $verifier = new Validation\Validator($app['controller.exception'], $app['config'], $app['resources']);

                return $verifier;
            }
        );

        $app['boot.listener.checks'] = $app->share(
            function ($app) {
                return new Listener\BootInitListener($app);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['boot.listener.checks']);
    }
}
