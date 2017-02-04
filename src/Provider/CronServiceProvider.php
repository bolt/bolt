<?php

namespace Bolt\Provider;

use Bolt\Cron;
use Silex;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class CronServiceProvider implements ServiceProviderInterface
{
    public function register(Silex\Application $app)
    {
        $app['cron'] = 
            function ($app) {
                $cron = new Cron($app, new BufferedOutput());

                return $cron;
            }
        ;
    }

    public function boot(Silex\Application $app)
    {
    }
}
