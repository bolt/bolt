<?php
namespace Bolt\Provider;

use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Database\Schema\Table;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DatabaseSchemaProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['schema'] = $app->share(
            function ($app) {
                return new Manager($app);
            }
        );

        $app['schema.tables'] = $app->share(function (Application $app) {
            /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
            $platform = $app['db']->getDatabasePlatform();

            // @codingStandardsIgnoreStart
            $acne = new \Pimple([
                'authtoken'  => $app->share(function () use ($platform) { return new Table\AuthToken($platform); }),
                'cron'       => $app->share(function () use ($platform) { return new Table\Cron($platform); }),
                'log_change' => $app->share(function () use ($platform) { return new Table\LogChange($platform); }),
                'log_system' => $app->share(function () use ($platform) { return new Table\LogSystem($platform); }),
                'relations'  => $app->share(function () use ($platform) { return new Table\Relations($platform); }),
                'taxonomy'   => $app->share(function () use ($platform) { return new Table\Taxonomy($platform); }),
                'users'      => $app->share(function () use ($platform) { return new Table\Users($platform); }),
            ]);

            foreach ($app['config']->get('contenttypes') as $contenttype) {
                $acne[$contenttype['tablename']] = $app->share(function () use ($platform) { return new Table\ContentType($platform); });
            }
            // @codingStandardsIgnoreEnd

            return $acne;
        });
    }

    public function boot(Application $app)
    {
    }
}
