<?php

namespace Bolt\Provider;

use Bolt\Logger\Handler\SystemHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Monolog provider for Bolt system logging entries
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoggerSystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['logger.system'] = $app->share(function ($app) {
            $log = new Logger('logger.system');

            $log->pushHandler(new SystemHandler($app));

            return $log;
        });
    }

    public function boot(Application $app)
    {
    }
}
