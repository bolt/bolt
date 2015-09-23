<?php

namespace Bolt\Provider;

use RandomLib;
use SecurityLib\Strength;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['randomgenerator'] = $app->share(
            function () {
                $factory = new RandomLib\Factory();

                return $factory->getGenerator(new Strength(Strength::MEDIUM));
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
