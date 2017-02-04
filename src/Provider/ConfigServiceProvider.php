<?php

namespace Bolt\Provider;

use Bolt;
use Bolt\Config;
use Bolt\Configuration\Environment;
use Bolt\Configuration\Validation\Validator as ConfigValidator;
use Bolt\EventListener\ConfigListener;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pimple\Container;
use Silex\Api\BootableProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['config'] = 
            function ($app) {
                $config = new Config($app);
                $config->initialize();

                return $config;
            }
        ;

        $app['config.environment'] = 
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
        ;

        $app['config.validator'] = 
            function ($app) {
                $validator = new ConfigValidator(
                    $app['controller.exception'],
                    $app['config'],
                    $app['resources'],
                    $app['logger.flash']
                );

                return $validator;
            }
        ;

        $app['config.listener'] = 
            function ($app) {
                return new ConfigListener($app);
            }
        ;
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
