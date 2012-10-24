<?php

namespace Bolt;

use Bolt;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['cache'] = $app->share(function () {

            $cache = new Bolt\Cache();

            return $cache;

        });

    }

    public function boot(Application $app)
    {
    }
}
