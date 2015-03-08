<?php

namespace Bolt\Provider;

use Bolt\Storage;
use Bolt\Mapping\MetadataDriver;
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
        
        $app['storage.metadata'] = $app->share(
            function ($app) {
                $meta = new MetadataDriver($app['integritychecker']);

                return $meta;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
