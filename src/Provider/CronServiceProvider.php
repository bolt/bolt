<?php

namespace Bolt\Provider;

use Bolt\Cron;
use Silex;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Pimple\Container;

class CronServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['cron'] = 
            function ($app) {
                $cron = new Cron($app, new BufferedOutput());

                return $cron;
            }
        ;
    }
}
