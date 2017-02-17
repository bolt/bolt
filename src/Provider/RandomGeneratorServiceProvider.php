<?php

namespace Bolt\Provider;

use Bolt\Security\Random\Generator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['randomgenerator'] = function () {
            return new Generator();
        };
    }
}
