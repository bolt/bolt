<?php

namespace Bolt\Provider;

use Bolt\Helpers\RandomLibFactory;
use SecurityLib\Strength;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['randomgenerator'] = $app->share(
            function () {
                $factory = new RandomLibFactory();

                return $factory->getGenerator(new Strength(Strength::MEDIUM));
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
