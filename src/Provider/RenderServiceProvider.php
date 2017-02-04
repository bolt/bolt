<?php

namespace Bolt\Provider;

use Bolt\Render;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['render'] = 
            function ($app) {
                return new Render($app);
            }
        ;

        $app['safe_render'] = 
            function ($app) {
                return new Render($app, true);
            }
        ;
    }
}
