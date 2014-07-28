<?php

namespace Bolt\Provider;

use Bolt\Storage;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['storage'] = $app->share(
            function ($app) {
                $storage = new Storage($app);

                return $storage;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
