<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Database\IntegrityChecker;

class IntegrityCheckerProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['integritychecker'] = $app->share(
            function ($app) {
                return new IntegrityChecker($app);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
