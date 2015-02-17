<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Storage\Prefill;

class PrefillServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['prefill'] = $app->share(
            function ($app) {
                $prefill = new Prefill($app['guzzle.client']);
                return $prefill;
            }
        );

    }

    public function boot(Application $app)
    {
    }
}
