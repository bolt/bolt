<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\DataCollector\TwigDataCollector;

class SafeTwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['safe_twig'] = $app->share(function($app) {
            $loader = new \Twig_Loader_String();
            return new \Twig_Environment($loader);
        });
    }

    public function boot(Application $app)
    {
    }
}

