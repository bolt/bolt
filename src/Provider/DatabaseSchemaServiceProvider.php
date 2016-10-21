<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Storage\Database\Schema\Builder;
use Bolt\Storage\Database\Schema\LazySchemaManager;
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
class DatabaseSchemaServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['schema'] = $app->share(
            function ($app) {
                return new Manager($app);
            }
        );
        $app['schema.lazy'] = $app->share(
            function ($app) {
                return new LazySchemaManager(
                    function () use ($app) {
                        return $app['schema'];
                    }
                );
            }
        );

        $app['schema.prefix'] = $app->share(
            function ($app) {
                $prefix = $app['config']->get('general/database/prefix', 'bolt_');

                return rtrim($prefix, '_') . '_';
            }
        );

        $app['schema.tables_filter'] = function () use ($app) {
            $prefix = $app['config']->get('general/database/prefix');

            return "/^$prefix.+/";
        };

        $app['schema.charset'] = $app->share(
            function ($app) {
                return $app['config']->get('general/database/charset', 'utf8');
            }
        );

        $app['schema.collate'] = $app->share(
            function ($app) {
                return $app['config']->get('general/database/collate', 'utf8_unicode_ci');
            }
        );

        /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
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
                $prefix = $app['schema.prefix'];

                // @codingStandardsIgnoreStart
                return new \Pimple([
                    'authtoken'   => $app->share(function () use ($platform, $prefix) { return new Table\AuthToken($platform, $prefix); }),
                    'cron'        => $app->share(function () use ($platform, $prefix) { return new Table\Cron($platform, $prefix); }),
                    'field_value' => $app->share(function () use ($platform, $prefix) { return new Table\FieldValue($platform, $prefix); }),
                    'log_change'  => $app->share(function () use ($platform, $prefix) { return new Table\LogChange($platform, $prefix); }),
                    'log_system'  => $app->share(function () use ($platform, $prefix) { return new Table\LogSystem($platform, $prefix); }),
                    'relations'   => $app->share(function () use ($platform, $prefix) { return new Table\Relations($platform, $prefix); }),
                    'taxonomy'    => $app->share(function () use ($platform, $prefix) { return new Table\Taxonomy($platform, $prefix); }),
                    'users'       => $app->share(function () use ($platform, $prefix) { return new Table\Users($platform, $prefix); }),
                ]);
                // @codingStandardsIgnoreEnd
            }
        );

        // Schemas of the ContentType tables
        $app['schema.content_tables'] = $app->share(
            function (Application $app) {
                /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
                $platform = $app['db']->getDatabasePlatform();
                $prefix = $app['schema.prefix'];

                $contentTypes = $app['config']->get('contenttypes');
                $acne = new \Pimple();

                foreach (array_keys($contentTypes) as $contentType) {
                    // @codingStandardsIgnoreStart
                    $tableName = $contentTypes[$contentType]['tablename'];
                    $acne[$tableName] = $app->share(function () use ($platform, $prefix) { return new Table\ContentType($platform, $prefix); });
                    // @codingStandardsIgnoreEnd
                }

                return $acne;
            }
        );

        // Schemas (empty) of the extension tables
        $app['schema.extension_tables'] = $app->share(
            function (Application $app) {
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
                                $app['schema.charset'],
                                $app['schema.collate'],
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
                                $app['schema.charset'],
                                $app['schema.collate'],
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
                                $app['schema.charset'],
                                $app['schema.collate'],
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
                return new Timer($app['filesystem.cache']->getFile(Timer::CHECK_TIMESTAMP_FILE));
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
                    $app['schema.prefix'],
                    $app['logger.system']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
