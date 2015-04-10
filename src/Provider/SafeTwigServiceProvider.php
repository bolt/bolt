<?php

namespace Bolt\Provider;

use Bolt\Twig\TwigExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SafeTwigServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['safe_twig.bolt_extension'] = function ($app) {
            return new TwigExtension($app, $app['twig.handlers'], true);
        };

        $app['safe_twig'] = $app->share(
            function ($app) {
                $loader = new \Twig_Loader_String();

                $twig = new \Twig_Environment($loader);
                $twig->addExtension($app['safe_twig.bolt_extension']);

                return $twig;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
