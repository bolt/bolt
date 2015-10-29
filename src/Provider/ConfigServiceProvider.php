<?php

namespace Bolt\Provider;

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
                $srcRoot = $app['resources']->getPath('root');
                $webRoot = $app['resources']->getPath('web');

                $environment = new Environment(
                    $srcRoot,
                    $webRoot,
                    $app['cache'],
                    $app['bolt_name'],
                    $app['bolt_version']
                );

                return $environment;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
