<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['config'] = $app->share(function ($app) {

            $config = new Bolt\Config($app);

            return $config;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
