<?php

namespace Bolt\Provider;

use Bolt\Storage\Database\Schema\Builder;
use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Database\Schema\Table;
use Bolt\Storage\Database\Schema\Timer;
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

        $app['schema.prefix'] = $app->share(
            function ($app) {
                $prefix = $app['config']->get('general/database/prefix', 'bolt_');

                return rtrim($prefix, '_') . '_';
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
        $app['schema.base_tables'] = $app->share(
            function (Application $app) {
                /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                $platform = $app['db']->getDatabasePlatform();

                // @codingStandardsIgnoreStart
                return new \Pimple([
                    'authtoken'   => $app->share(function () use ($platform) { return new Table\AuthToken($platform); }),
                    'cron'        => $app->share(function () use ($platform) { return new Table\Cron($platform); }),
                    'field_value' => $app->share(function () use ($platform) { return new Table\FieldValue($platform); }),
                    'log_change'  => $app->share(function () use ($platform) { return new Table\LogChange($platform); }),
                    'log_system'  => $app->share(function () use ($platform) { return new Table\LogSystem($platform); }),
                    'relations'   => $app->share(function () use ($platform) { return new Table\Relations($platform); }),
                    'taxonomy'    => $app->share(function () use ($platform) { return new Table\Taxonomy($platform); }),
                    'users'       => $app->share(function () use ($platform) { return new Table\Users($platform); }),
                ]);
                // @codingStandardsIgnoreEnd
            }
        );

        // Schemas of the ContentType tables
        $app['schema.content_tables'] = $app->share(
            function (Application $app) {
                /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                $platform = $app['db']->getDatabasePlatform();

                $contenttypes = $app['config']->get('contenttypes');
                $acne = new \Pimple();

                foreach (array_keys($contenttypes) as $contenttype) {
                    // @codingStandardsIgnoreStart
                    $acne[$contenttype] = $app->share(function () use ($platform) { return new Table\ContentType($platform); });
                    // @codingStandardsIgnoreEnd
                }

                return $acne;
            }
        );

        // Schemas (empty) of the extension tables
        $app['schema.extension_tables'] = $app->share(
            function (Application $app) {
                /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                $platform = $app['db']->getDatabasePlatform();

                return new \Pimple([]);
            }
        );

        // Combined schemas of all Bolt tables.
        $app['schema.tables'] = $app->share(
            function (Application $app) {
                $acne = new \Pimple();

                foreach ($app['schema.base_tables']->keys() as $baseName) {
                    // @codingStandardsIgnoreStart
                    $acne[$baseName] = $app->share(function () use ($app, $baseName) { return $app['schema.base_tables'][$baseName]; });
                    // @codingStandardsIgnoreEnd
                }

                foreach ($app['schema.content_tables']->keys() as $baseName) {
                    // @codingStandardsIgnoreStart
                    $acne[$baseName] = $app->share(function () use ($app, $baseName) { return $app['schema.content_tables'][$baseName]; });
                    // @codingStandardsIgnoreEnd
                }

                foreach ($app['schema.extension_tables']->keys() as $baseName) {
                    // @codingStandardsIgnoreStart
                    $acne[$baseName] = $app->share(function () use ($app, $baseName) { return $app['schema.extension_tables'][$baseName]; });
                    // @codingStandardsIgnoreEnd
                }

                return $acne;
            }
        );

        $app['schema.builder'] = $app->share(
            function ($app) {
                return new \Pimple([
                    'base'       => $app->share(
                        function () use ($app) {
                            return new Builder\BaseTables(
                                $app['db'],
                                $app['schema'],
                                $app['schema.base_tables'],
                                $app['schema.prefix'],
                                $app['logger.system'],
                                $app['logger.flash']
                            );
                        }
                    ),
                    'content'    => $app->share(
                        function () use ($app) {
                            return new Builder\ContentTables(
                                $app['db'],
                                $app['schema'],
                                $app['schema.content_tables'],
                                $app['schema.prefix'],
                                $app['logger.system'],
                                $app['logger.flash']
                            );
                        }
                    ),
                    'extensions' => $app->share(
                        function () use ($app) {
                            return new Builder\ExtensionTables(
                                $app['db'],
                                $app['schema'],
                                $app['schema.extension_tables'],
                                $app['schema.prefix'],
                                $app['logger.system'],
                                $app['logger.flash']
                            );
                        }
                    ),
                ]);
            }
        );

        $app['schema.timer'] = $app->share(
            function ($app) {
                return new Timer($app['resources']->getPath('cache'));
            }
        );

        $app['schema.comparator.factory'] = $app->protect(
            function () use ($app) {
                $platforms = [
                    'mysql'      => '\\Bolt\\Storage\\Database\\Schema\\Comparison\\MySql',
                    'postgresql' => '\\Bolt\\Storage\\Database\\Schema\\Comparison\\PostgreSql',
                    'sqlite'     => '\\Bolt\\Storage\\Database\\Schema\\Comparison\\Sqlite',
                ];
                $platformName = $app['db']->getDatabasePlatform()->getName();

                return $platforms[$platformName];
            }
        );

        $app['schema.comparator'] = $app->share(
            function ($app) {
                $comparator = $app['schema.comparator.factory']();

                return new $comparator(
                    $app['db'],
                    $app['schema'],
                    $app['schema.tables'],
                    $app['logger.system']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
