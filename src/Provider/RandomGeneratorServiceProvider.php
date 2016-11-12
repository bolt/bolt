<?php

namespace Bolt\Provider;

use Bolt\Security\Random\Generator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['randomgenerator'] = $app->share(
            function () {
                return new Generator();
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
