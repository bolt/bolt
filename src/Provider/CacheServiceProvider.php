<?php

namespace Bolt\Provider;

use Bolt\Cache;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['cache'] = $app->share(
            function (Application $app) {
                $cache = new Cache($app['resources']->getPath('cache'), $app);

                return $cache;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
