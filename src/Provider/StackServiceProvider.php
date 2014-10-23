<?php

namespace Bolt\Provider;

use Bolt\Stack;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StackServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['stack'] = $app->share(
            function ($app) {
                $stack = new Stack($app);

                return $stack;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
