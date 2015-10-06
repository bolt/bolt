<?php

namespace Bolt\Provider;

use Bolt\Render;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['render'] = $app->share(
            function ($app) {
                return new Render($app);
            }
        );

        $app['safe_render'] = $app->share(
            function ($app) {
                return new Render($app, true);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
