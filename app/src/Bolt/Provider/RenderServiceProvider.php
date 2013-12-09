<?php

namespace Bolt\Provider;

use Bolt\Render;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['render'] = $app->share(function ($app) {

            $render = new Render($app);

            return $render;

        });

    }

    public function boot(Application $app)
    {
    }
}
