<?php

namespace Bolt;

use Bolt;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['extensions'] = $app->share(function ($app) {

            $extensions = new Bolt\Extensions($app);

            return $extensions;

        });


    }

    public function boot(Application $app)
    {
    }
}

