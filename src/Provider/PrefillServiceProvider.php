<?php

namespace Bolt\Provider;

use Bolt\Storage\Prefill;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class PrefillServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['prefill'] = 
            function ($app) {
                $prefill = new Prefill($app['guzzle.client']);

                return $prefill;
            }
        ;
    }
}
