<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['cache'] = $app->share(function () {

            $config = new Bolt\Config();

            return $config;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
