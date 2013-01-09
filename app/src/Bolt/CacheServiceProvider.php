<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['cache'] = $app->share(function () {

            $cache = new Bolt\Cache();

            return $cache;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
