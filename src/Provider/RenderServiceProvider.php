<?php

namespace Bolt\Provider;

use Bolt\Render;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
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

    public function boot(Application $app)
    {
    }
}
