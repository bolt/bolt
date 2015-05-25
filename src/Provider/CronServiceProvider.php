<?php

namespace Bolt\Provider;

use Bolt\Cron;
use Silex;
use Silex\ServiceProviderInterface;

class CronServiceProvider implements ServiceProviderInterface
{
    public function register(Silex\Application $app)
    {
        $app['cron'] = $app->share(
            function ($app) {
                $cron = new Cron($app);

                return $cron;
            }
        );
    }

    public function boot(Silex\Application $app)
    {
    }
}
