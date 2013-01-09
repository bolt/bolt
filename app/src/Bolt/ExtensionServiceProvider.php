<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['extensions'] = $app->share(function ($app) {

            $extensions = new Bolt\Extensions($app);

            return $extensions;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
