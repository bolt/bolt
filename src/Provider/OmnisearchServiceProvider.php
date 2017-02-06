<?php

namespace Bolt\Provider;

use Bolt\Omnisearch;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class OmnisearchServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['omnisearch'] = 
            function ($app) {
                $omnisearch = new Omnisearch($app);

                return $omnisearch;
            }
        ;
    }
}
