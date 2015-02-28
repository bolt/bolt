<?php

namespace Bolt\Provider;

use Bolt\Omnisearch;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OmnisearchServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['omnisearch'] = $app->share(
            function ($app) {
                $omnisearch = new Omnisearch($app);

                return $omnisearch;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
