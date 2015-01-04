<?php

namespace Bolt\Provider;

use Bolt\Logger\Manager;
use Bolt\Logger\Handler\SystemHandler;
use Bolt\Logger\Handler\RecordChangeHandler;
use Monolog\Formatter\WildfireFormatter;
use Monolog\Handler\FirePHPHandler;
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
            $log = new Logger('logger.change');

            $log->pushHandler(new RecordChangeHandler($app));

            return $log;
        });

        // Firebug
        $app['logger.firebug'] = $app->share(function ($app) {
            $log = new Logger('logger.firebug');
            $handler = new FirePHPHandler();
            $handler->setFormatter(new WildfireFormatter());

            $log->pushHandler($handler);

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
