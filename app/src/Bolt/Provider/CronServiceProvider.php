<?php

namespace Bolt\Provider;

use Bolt\Controllers\Cron;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CronServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['cron'] = $app->share(
            function ($app) {
                $cron = new Cron($app);

                return $cron;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
