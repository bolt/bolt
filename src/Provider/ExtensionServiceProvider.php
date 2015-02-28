<?php

namespace Bolt\Provider;

use Bolt\Extensions;
use Bolt\Extensions\StatService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['extensions'] = $app->share(
            function ($app) {
                $extensions = new Extensions($app);

                return $extensions;
            }
        );

        $app['extensions.stats'] = $app->share(
            function ($app) {
                $stats = new StatService($app);

                return $stats;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
