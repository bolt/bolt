<?php

namespace Bolt\Provider;

use Bolt\Logger\Manager;
use Bolt\Logger\Handler\SystemHandler;
use Bolt\Logger\Handler\RecordChangeHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Monolog provider for Bolt system logging entries
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // System log
        $app['logger.system'] = $app->share(function ($app) {
            $log = new Logger('logger.system');

            $log->pushHandler(new SystemHandler($app));

            return $log;
        });

        // Changelog
        $app['logger.change'] = $app->share(function ($app) {
            $log = new Logger('logger.system');

            $log->pushHandler(new RecordChangeHandler($app));

            return $log;
        });

        // Manager
        $app['logger.manager'] = $app->share(function ($app) {
            $mgr = new Manager($app);

            return $mgr;
        });
    }

    public function boot(Application $app)
    {
    }
}
