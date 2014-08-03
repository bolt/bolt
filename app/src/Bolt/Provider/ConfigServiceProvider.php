<?php

namespace Bolt\Provider;

use Bolt\Config;
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
    }

    public function boot(Application $app)
    {
    }
}
