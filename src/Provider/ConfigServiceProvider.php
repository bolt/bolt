<?php

namespace Bolt\Provider;

use Bolt;
use Bolt\Config;
use Bolt\Configuration\Environment;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['config'] = $app->share(
            function ($app) {
                $config = new Config($app);

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
    }

    public function boot(Application $app)
    {
        $app['config']->doReplacements();
    }
}
