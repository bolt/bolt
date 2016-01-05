<?php

namespace Bolt\Provider;

use Bolt\Controllers\Cron;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class CronServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['cron'] = $app->share(
            function ($app) {
                $cron = new Cron($app, new BufferedOutput());

                return $cron;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
