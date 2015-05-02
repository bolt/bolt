<?php

namespace Bolt\Provider;

use Bolt\Logger\ChangeLog;
use Bolt\Logger\DeprecatedLog;
use Bolt\Logger\Handler\RecordChangeHandler;
use Bolt\Logger\Handler\SystemHandler;
use Bolt\Logger\Manager;
use Monolog\Formatter\WildfireFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Monolog provider for Bolt system logging entries.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        /**
         * Wrapper for old log service, used by extensions.
         *
         * @deprecated To be removed for Bolt 3.0
         */
        $app['log'] = $app->share(
            function ($app) {
                $log = new DeprecatedLog($app);

                return $log;
            }
        );

        // System log
        $app['logger.system'] = $app->share(
            function ($app) {
                $log = new Logger('logger.system');
                $log->pushHandler(new SystemHandler($app, Logger::INFO));

                return $log;
            }
        );

        // Changelog
        $app['logger.change'] = $app->share(
            function ($app) {
                $log = new Logger('logger.change');
                $log->pushHandler(new RecordChangeHandler($app));

                return $log;
            }
        );

        // Firebug
        $app['logger.firebug'] = $app->share(
            function ($app) {
                $log = new Logger('logger.firebug');
                $handler = new FirePHPHandler();
                $handler->setFormatter(new WildfireFormatter());
                $log->pushHandler($handler);

                return $log;
            }
        );

        // Manager
        $app['logger.manager'] = $app->share(
            function ($app) {
                $mgr = new Manager($app);

                return $mgr;
            }
        );

        // Change Log Manager
        $app['logger.manager.change'] = $app->share(
            function ($app) {
                $mgr = new ChangeLog($app);

                return $mgr;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
