<?php

namespace Bolt\Provider;

use Bolt;
use Bolt\Config;
use Bolt\Configuration\Environment;
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
                $boltPath = $app['path_resolver']->resolve('bolt');
                $boltAssetsPath = $app['path_resolver']->resolve('bolt_assets');

                $environment = new Environment(
                    $boltPath,
                    $boltAssetsPath,
                    $app['cache'],
                    $app['extend.action'],
                    Bolt\Version::VERSION
                );

                return $environment;
            }
        );

        $app['config.validator'] = $app->share(
            function ($app) {
                $validator = new ConfigValidator(
                    $app['config'],
                    $app['resources'],
                    $app['logger.flash']
                );

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
