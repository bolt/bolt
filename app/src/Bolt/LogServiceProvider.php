<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class LogServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['log'] = $app->share(function ($app) {

            $log = new Bolt\Log($app);

            return $log;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
