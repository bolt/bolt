<?php

namespace Bolt\Provider;

use Bolt\Helpers\Deprecated;
use Bolt\Render;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['render'] = $app->share(
            function ($app) {
                Deprecated::service('render', 3.3, 'twig');

                return new Render($app);
            }
        );

        $app['safe_render'] = $app->share(
            function ($app) {
                Deprecated::service('render', 3.3, 'Use "twig" service with sandbox enabled instead.');

                return new Render($app, true);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
