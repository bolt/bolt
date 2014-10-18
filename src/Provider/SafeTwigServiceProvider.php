<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class SafeTwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['safe_twig'] = $app->share(
            function () {
                $loader = new \Twig_Loader_String();

                return new \Twig_Environment($loader);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
