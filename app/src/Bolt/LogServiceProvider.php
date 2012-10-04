<?php

namespace Bolt;

use Bolt;
use Silex\Application;
use Silex\ServiceProviderInterface;

class LogServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['log'] = $app->share(function ($app) {

            $log = new Bolt\Log($app);

            return $log;

        });


    }

    public function boot(Application $app)
    {
    }
}

