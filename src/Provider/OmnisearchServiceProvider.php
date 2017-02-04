<?php

namespace Bolt\Provider;

use Bolt\Omnisearch;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class OmnisearchServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['omnisearch'] = 
            function ($app) {
                $omnisearch = new Omnisearch($app);

                return $omnisearch;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
