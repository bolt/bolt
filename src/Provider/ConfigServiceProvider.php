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

        if (!isset($app['config.pre_boot'])) {
            $this->preBoot($app['resources']);
            $app['config.pre_boot'] = true;
        }
    }

    /**
     * Internal pre-boot checks.
     *
     * @param ResourceManager $resources
     */
    private function preBoot(ResourceManager $resources)
    {
        PreBoot\ConfigurationFile::checkConfigFiles(
            ['config', 'contenttypes', 'menu', 'permissions', 'routing', 'taxonomy'],
            $resources->getPath('src/../app/config'),
            $resources->getPath('config')
        );
    }

    public function boot(Application $app)
    {
        $app['config']->doReplacements();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['config.listener']);
    }
}
