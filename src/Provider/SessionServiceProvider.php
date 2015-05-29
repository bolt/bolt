<?php

namespace Bolt\Provider;

use Bolt\Session\Generator\NativeGenerator;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionStorage;
use Bolt\Session\StorageListener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;

/**
 * Because screw PHP core.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['session'] = $app->share(function ($app) {
            return new Session($app['session.storage']);
        });

        $app['session.storage'] = $app->share(function ($app) {
            return new SessionStorage(
                new OptionsBag($app['session.storage.options']),
                $app['session.storage.handler'],
                $app['session.storage.generator'],
                $app['session.storage.serializer']
            );
        });

        $app['session.storage.handler'] = $app->share(function () {
            return new NullSessionHandler();
        });

        $app['session.storage.generator'] = $app->share(function () {
            return new NativeGenerator();
        });

        $app['session.storage.serializer'] = $app->share(function () {
            return new NativeSerializer();
        });
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber(new StorageListener($app['session.storage']));
    }
}
