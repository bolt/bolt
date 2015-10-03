<?php
namespace Bolt\Provider;

use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Database\Schema\Table;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Bolt database storage service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseSchemaProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['schema'] = $app->share(
            function ($app) {
                return new Manager($app);
            }
        );

        /** @deprecated Will be removed in Bolt 3 */
        $app['integritychecker'] = $app->share(
            function ($app) {
                $app['logger.system']->warning("[DEPRECATED]: An extension is using app['integritychecker'] and this has been replaced with app['schema'].", ['event' => 'deprecated']);

                return $app['schema'];
            }
        );

        // Schemas of the Bolt base tables.
        $app['schema.base_tables'] = $app->share(function (Application $app) {
            /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
            $platform = $app['db']->getDatabasePlatform();

            // @codingStandardsIgnoreStart
            return new \Pimple([
                'authtoken'   => $app->share(function () use ($platform) { return new Table\AuthToken($platform); }),
                'cron'        => $app->share(function () use ($platform) { return new Table\Cron($platform); }),
                'field'       => $app->share(function () use ($platform) { return new Table\Field($platform); }),
                'field_value' => $app->share(function () use ($platform) { return new Table\FieldValue($platform); }),
                'log_change'  => $app->share(function () use ($platform) { return new Table\LogChange($platform); }),
                'log_system'  => $app->share(function () use ($platform) { return new Table\LogSystem($platform); }),
                'relations'   => $app->share(function () use ($platform) { return new Table\Relations($platform); }),
                'taxonomy'    => $app->share(function () use ($platform) { return new Table\Taxonomy($platform); }),
                'users'       => $app->share(function () use ($platform) { return new Table\Users($platform); }),
            ]);
            // @codingStandardsIgnoreEnd
        });

        // Schemas of all Bolt tables.
        $app['schema.tables'] = $app->share(function (Application $app) {
            $acne = new \Pimple();

            foreach ($app['schema.base_tables']->keys() as $baseName) {
                $acne[$baseName] = $app['schema.base_tables'][$baseName];
            }

            /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
            $platform = $app['db']->getDatabasePlatform();
            foreach ($app['config']->get('contenttypes') as $contenttype) {
                $serviceName = 'schema.' . $contenttype['tablename'];
                // @codingStandardsIgnoreStart
                $acne[$serviceName] = $app->share(function () use ($platform) { return new Table\ContentType($platform); });
                // @codingStandardsIgnoreEnd
            }

            return $acne;
        });
    }

    public function boot(Application $app)
    {
    }
}
