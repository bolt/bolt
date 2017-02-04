<?php

namespace Bolt\Provider;

use Bolt\Security\Random\Generator;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['randomgenerator'] = 
            function () {
                return new Generator();
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
