<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['storage'] = $app->share(function ($app) {

            $storage = new Bolt\Storage($app);

            return $storage;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
