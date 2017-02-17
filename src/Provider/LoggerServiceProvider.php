<?php

namespace Bolt\Provider;

use Bolt\Logger\FlashLogger;
use Bolt\Logger\Handler\RecordChangeHandler;
use Bolt\Logger\Handler\SystemHandler;
use Bolt\Logger\Manager;
use Bolt\Storage\Entity;
use Monolog\Formatter\WildfireFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\MonologServiceProvider;

/**
 * Monolog provider for Bolt system logging entries.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // System log
        $app['logger.system'] = function ($app) {
            $log = new Logger('logger.system');
            $log->pushHandler($app['monolog.handler']);
            $log->pushHandler(new SystemHandler($app, Logger::INFO));

            return $log;
        };

        // Changelog
        $app['logger.change'] = function ($app) {
            $log = new Logger('logger.change');
            $log->pushHandler(new RecordChangeHandler($app));

            return $log;
        };

        // Firebug
        $app['logger.firebug'] = function () {
            $log = new Logger('logger.firebug');
            $handler = new FirePHPHandler();
            $handler->setFormatter(new WildfireFormatter());
            $log->pushHandler($handler);

            return $log;
        };

        // System log
        $app['logger.flash'] = function () {
            $log = new FlashLogger();

            return $log;
        };

        // Manager
        $app['logger.manager'] = function ($app) {
            $changeRepository = $app['storage']->getRepository(Entity\LogChange::class);
            $systemRepository = $app['storage']->getRepository(Entity\LogSystem::class);
            $mgr = new Manager($app, $changeRepository, $systemRepository);

            return $mgr;
        };

        $app->register(new MonologServiceProvider(), ['monolog.name' => 'bolt']);

        $app['monolog.level'] = function ($app) {
            return Logger::toMonologLevel($app['config']->get('general/debuglog/level'));
        };

        $app['monolog.logfile'] = function ($app) {
            return $app['path_resolver']->resolve('%var%/log/' . $app['config']->get('general/debuglog/filename'));
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

        $app['logger.debug'] = function ($app) {
            return $app['monolog'];
        };
    }
}
