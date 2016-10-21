<?php

namespace Bolt\Provider;

use Bolt\Logger\FlashLogger;
use Bolt\Logger\Handler\RecordChangeHandler;
use Bolt\Logger\Handler\SystemHandler;
use Bolt\Logger\Manager;
use Monolog\Formatter\WildfireFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\Provider\MonologServiceProvider;
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
        // System log
        $app['logger.system'] = $app->share(
            function ($app) {
                $log = new Logger('logger.system');
                $log->pushHandler($app['monolog.handler']);
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

        // System log
        $app['logger.flash'] = $app->share(
            function ($app) {
                $log = new FlashLogger();

                return $log;
            }
        );

        // Manager
        $app['logger.manager'] = $app->share(
            function ($app) {
                $changeRepository = $app['storage']->getRepository('Bolt\Storage\Entity\LogChange');
                $systemRepository = $app['storage']->getRepository('Bolt\Storage\Entity\LogSystem');
                $mgr = new Manager($app, $changeRepository, $systemRepository);

                return $mgr;
            }
        );

        $app->register(
            new MonologServiceProvider(),
            [
                'monolog.name' => 'bolt',
            ]
        );

        $app['monolog.level'] = function ($app) {
            return Logger::toMonologLevel($app['config']->get('general/debuglog/level'));
        };

        $app['monolog.logfile'] = function ($app) {
            return $app['resources']->getPath('cache') . '/' . $app['config']->get('general/debuglog/filename');
        };

        $app['monolog.handler'] = $app->extend(
            'monolog.handler',
            function ($handler, $app) {
                // If we're not debugging, just send to /dev/null
                if (!$app['config']->get('general/debuglog/enabled')) {
                    return new NullHandler();
                }

                return $handler;
            }
        );

        // If we're not debugging, just send to /dev/null
        if (!$app['config']->get('general/debuglog/enabled')) {
            $app['monolog.handler'] = function () {
                return new NullHandler();
            };
        }

        $app['logger.debug'] = function () use ($app) {
            return $app['monolog'];
        };
    }

    public function boot(Application $app)
    {
    }
}
