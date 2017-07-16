<?php

namespace Bolt\Provider;

use Bolt\Omnisearch;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class OmnisearchServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['omnisearch'] = function ($app) {
            $omnisearch = new Omnisearch($app);

            return $omnisearch;
        };
    }
}
