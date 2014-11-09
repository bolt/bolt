<?php

namespace Bolt\Provider;

use Bolt\Logger\Handler\RecordChangeHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Monolog provider for Bolt record changelog entries
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LoggerRecordChangeServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['logger.change'] = $app->share(function ($app) {
            $log = new Logger('logger.system');

            $log->pushHandler(new RecordChangeHandler($app));

            return $log;
        });
    }

    public function boot(Application $app)
    {
    }
}
