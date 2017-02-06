<?php

namespace Bolt\Provider;

use Bolt\Security\Random\Generator;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['randomgenerator'] = 
            function () {
                return new Generator();
            }
        ;
    }
}
