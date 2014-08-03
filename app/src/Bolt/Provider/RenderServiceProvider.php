<?php

namespace Bolt\Provider;

use Bolt\Render;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RenderServiceProvider implements ServiceProviderInterface
{
    public function __construct($safe = false)
    {
        $this->safe = $safe;
    }

    public function register(Application $app)
    {
        if ($this->safe) {
            $app['render'] = $app->share(
                function ($app) {
                    return new Render($app);
                }
            );
        } else {
            $app['safe_render'] = $app->share(
                function ($app) {
                    return new Render($app, true);
                }
            );
        }
    }

    public function boot(Application $app)
    {
    }
}
