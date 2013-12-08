<?php

namespace Bolt\Provider;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['render'] = $app->share(function ($app) {

            $render = new Bolt\Render($app);

            return $render;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
