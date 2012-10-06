<?php

namespace Bolt;

use Bolt;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['storage'] = $app->share(function ($app) {

            $storage = new Bolt\Storage($app);

            return $storage;

        });


    }

    public function boot(Application $app)
    {
    }
}

