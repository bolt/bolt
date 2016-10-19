<?php

namespace Bolt\Provider;

use Bolt;
use Bolt\Config;
use Bolt\Configuration\Environment;
use Bolt\Configuration\PreBoot;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Validation\Validator as ConfigValidator;
use Bolt\EventListener\ConfigListener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['config'] = $app->share(
            function ($app) {
                $config = new Config($app);
                $config->initialize();

                return $config;
            }
        );

        $app['config.environment'] = $app->share(
            function ($app) {
                $appPath = $app['resources']->getPath('app');
                $viewPath = $app['resources']->getPath('view');

                $environment = new Environment(
                    $appPath,
                    $viewPath,
                    $app['cache'],
                    $app['extend.action'],
                    Bolt\Version::VERSION
                );

                return $environment;
            }
        );

        $app['config.validator'] = $app->share(
            function ($app) {
                $validator = new ConfigValidator($app['controller.exception'], $app['config'], $app['resources']);

                return $validator;
            }
        );

        $app['config.listener'] = $app->share(
            function ($app) {
                return new ConfigListener($app);
            }
        );
    }

    public function boot(Application $app)
    {
        $app['config']->doReplacements();

        $app['config.environment']->checkVersion();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['config.listener']);
    }
}
