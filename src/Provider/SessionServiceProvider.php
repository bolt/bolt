<?php

namespace Bolt\Provider;

use Bolt\Session\FileSessionHandler;
use Bolt\Session\Generator\RandomGenerator;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionStorage;
use Bolt\Session\SessionListener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Because screw PHP core.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $this->registerSessions($app);
        $this->registerListeners($app);
        $this->registerOptions($app);
        $this->registerHandlers($app);

        $app['session.storage.generator'] = $app->share(function () use ($app) {
            return new RandomGenerator($app['randomgenerator']);
        });

        $app['session.storage.serializer'] = $app->share(function () {
            return new NativeSerializer();
        });

        $app['session.bag.attribute'] = function () {
            return new AttributeBag();
        };

        $app['session.bag.flash'] = function () {
            return new FlashBag();
        };

        $app['session.bag.metadata'] = function () {
            return new MetadataBag();
        };
    }

    public function registerSessions(Application $app)
    {
        $app['sessions'] = $app->share(function () use ($app) {
            $app['sessions.options.initializer']();

            $sessions = new \Pimple();
            foreach ($app['sessions.options'] as $name => $options) {
                $sessions[$name] = $app->share(function () use ($options, $app) {
                    return $app['session.factory']($options);
                });
            }

            return $sessions;
        });

        $app['session.factory'] = $app->protect(function ($options) use ($app) {
            return new Session(
                $app['session.storage.factory']($options),
                $app['session.bag.attribute'],
                $app['session.bag.flash']
            );
        });

        $app['session.storage.factory'] = $app->protect(function ($options) use ($app) {
            return new SessionStorage(
                $options,
                $app['session.storage.handler.factory']($options['handler'], $options),
                $app['session.storage.generator'],
                $app['session.storage.serializer']
            );
        });

        $app['session'] = $app->share(function ($app) {
            // Sessions needs to be called first so sessions.default is initialized
            $sessions = $app['sessions'];
            return $sessions[$app['sessions.default']];
        });
    }

    public function registerListeners(Application $app)
    {
        $app['sessions.listener'] = $app->share(function () use ($app) {
            $app['sessions.options.initializer']();

            $listeners = new \Pimple();
            foreach ($app['sessions']->keys() as $name) {
                $setToRequest = $name === $app['sessions.default'];
                $listeners[$name] = $app->share(function () use ($app, $name, $setToRequest) {
                    $session = $app['sessions'][$name];
                    $options = $app['sessions.options'][$name];
                    return $app['session.listener.factory']($session, $options, $setToRequest);
                });
            }

            return $listeners;
        });

        $app['session.listener.factory'] = $app->protect(function ($session, $options, $setToRequest = false) use ($app) {
            return new SessionListener($session, $options, $setToRequest);
        });

        $app['session.listener'] = $app->share(function ($app) {
            return $app['session.listeners'][$app['sessions.default']];
        });
    }

    protected function registerOptions(Application $app)
    {
        $app['session.default_options'] = [
            'handler' => 'files',
        ];

        $app['sessions.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;

            if ($initialized) {
                return;
            }
            $initialized = true;

            //TODO Pull from "session.storage.options" for BC

            if (!isset($app['sessions.options'])) {
                $app['sessions.options'] = [
                    'default' => isset($app['session.options']) ? $app['session.options'] : [],
                ];
            }

            $options = [];
            foreach ($app['sessions.options'] as $name => $opts) {
                if (!isset($app['sessions.default'])) {
                    $app['sessions.default'] = $name;
                }
                $opts = array_replace($app['session.default_options'], (array) $opts);
                $options[$name] = new OptionsBag($opts);
            }
            $app['sessions.options'] = $options;
        });
    }

    protected function registerHandlers(Application $app)
    {
        $app['session.storage.handler.factory'] = $app->protect(function ($handler, $handlerOptions) use ($app) {
            $key = 'session.storage.handler.factory.' . $handler;
            if (isset($app[$key])) {
                return $app[$key]($handlerOptions);
            }
            throw new \RuntimeException("Unsupported handler type '$handler' specified");
        });

        $app['session.storage.handler.factory.files'] = $app->protect(function ($handlerOptions) {
            return new FileSessionHandler($handlerOptions['save_path']);
        });

        //TODO Moar handlers
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        /** @var \Pimple $listeners */
        $listeners = $app['sessions.listener'];
        foreach ($listeners->keys() as $name) {
            $dispatcher->addSubscriber($listeners[$name]);
        }
    }
}
