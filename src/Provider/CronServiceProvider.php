<?php

namespace Bolt\Provider;

use Bolt\Cron;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class CronServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['cron'] = function ($app) {
            $cron = new Cron($app, new BufferedOutput());

            return $cron;
        };
    }
}
