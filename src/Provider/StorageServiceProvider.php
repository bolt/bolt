<?php

namespace Bolt\Provider;

use Bolt\EventListener\StorageEventListener;
use Bolt\Storage;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['storage'] = $app->share(
            function ($app) {
                $storage = new Storage($app);

                return $storage;
            }
        );

        $app['storage.listener'] = $app->share(function () use ($app) {
            return new StorageEventListener($app['storage'], $app['config']);
        });
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['storage.listener']);
    }
}
