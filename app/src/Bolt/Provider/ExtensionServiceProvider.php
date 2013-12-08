<?php

namespace Bolt\Provider;

use Bolt\Extensions;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['extensions'] = $app->share(function ($app) {

            $extensions = new Extensions($app);

            return $extensions;

        });

    }

    public function boot(Application $app)
    {
    }
}
