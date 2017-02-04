<?php

namespace Bolt\Provider;

use Bolt\Storage\Prefill;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class PrefillServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['prefill'] = 
            function ($app) {
                $prefill = new Prefill($app['guzzle.client']);

                return $prefill;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
