<?php

namespace Bolt\Provider;

use Bolt\Database\IntegrityChecker;
use Bolt\Database\Table;
use Doctrine\DBAL\Schema\Schema;
use Silex\Application;
use Silex\ServiceProviderInterface;

class IntegrityCheckerProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['integritychecker'] = $app->share(
            function ($app) {
                return new IntegrityChecker($app);
            }
        );

        $app['integritychecker.tables'] = $app->share(function (Application $app) {
            return new \Pimple([
                // @codingStandardsIgnoreStart
                'authtoken'  => $app->share(function () use ($app) { return new Table\AuthToken(); }),
                'cron'       => $app->share(function () use ($app) { return new Table\Cron(); }),
                'log_change' => $app->share(function () use ($app) { return new Table\LogChange(); }),
                'log_system' => $app->share(function () use ($app) { return new Table\LogSystem(); }),
                'relations'  => $app->share(function () use ($app) { return new Table\Relations(); }),
                'taxonomy'   => $app->share(function () use ($app) { return new Table\Taxonomy(); }),
                'users'      => $app->share(function () use ($app) { return new Table\Users(); }),
                // @codingStandardsIgnoreEnd
            ]);
        });
    }

    public function boot(Application $app)
    {
    }
}
