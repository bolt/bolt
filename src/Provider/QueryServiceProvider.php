<?php

namespace Bolt\Provider;

use Bolt\Storage\NamingStrategy;
use Bolt\Storage\Query\Query;
use Silex\Application;
use Silex\ServiceProviderInterface;

class QueryServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['query'] = function ($app) {
            $runner = new Query($app['storage']);
            
            return $runner;
        };

    }

    public function boot(Application $app)
    {
    }
}
