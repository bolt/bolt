<?php

namespace Bolt\Provider;

use Bolt\Storage\Database\Schema\Builder;
use Bolt\Storage\Database\Schema\Comparison;
use Bolt\Storage\Database\Schema\LazySchemaManager;
use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Database\Schema\Table;
use Bolt\Storage\Database\Schema\Timer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

/**
 * Bolt database storage service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseSchemaServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['schema'] = function ($app) {
            return new Manager($app);
        };

        $app['schema.lazy'] = function ($app) {
            return new LazySchemaManager(
                function () use ($app) {
                    return $app['schema'];
                }
            );
        };

        $app['schema.prefix'] = function ($app) {
            $prefix = $app['config']->get('general/database/prefix', 'bolt_');

            return rtrim($prefix, '_') . '_';
        };

        $app['schema.tables_filter'] = function () use ($app) {
            $prefix = $app['config']->get('general/database/prefix');

            return "/^$prefix.+/";
        };

        $app['schema.charset'] = function ($app) {
            return $app['config']->get('general/database/charset', 'utf8');
        };

        $app['schema.collate'] = function ($app) {
            return $app['config']->get('general/database/collate', 'utf8_unicode_ci');
        };

        // Schemas of the Bolt base tables.
        $app['schema.base_tables'] = function (Application $app) {
            /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
            $platform = $app['db']->getDatabasePlatform();
            $prefix = $app['schema.prefix'];

            // @codingStandardsIgnoreStart
            return new Container([
                'authtoken'   => function () use ($platform, $prefix) { return new Table\AuthToken($platform, $prefix); },
                'cron'        => function () use ($platform, $prefix) { return new Table\Cron($platform, $prefix); },
                'field_value' => function () use ($platform, $prefix) { return new Table\FieldValue($platform, $prefix); },
                'log_change'  => function () use ($platform, $prefix) { return new Table\LogChange($platform, $prefix); },
                'log_system'  => function () use ($platform, $prefix) { return new Table\LogSystem($platform, $prefix); },
                'relations'   => function () use ($platform, $prefix) { return new Table\Relations($platform, $prefix); },
                'taxonomy'    => function () use ($platform, $prefix) { return new Table\Taxonomy($platform, $prefix); },
                'users'       => function () use ($platform, $prefix) { return new Table\Users($platform, $prefix); },
            ]);
            // @codingStandardsIgnoreEnd
        };

        // Schemas of the ContentType tables
        $app['schema.content_tables'] = function (Application $app) {
            /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
            $platform = $app['db']->getDatabasePlatform();
            $prefix = $app['schema.prefix'];

            $contentTypes = $app['config']->get('contenttypes');
            $acne = new Container();

            foreach (array_keys($contentTypes) as $contentType) {
                $tableName = $contentTypes[$contentType]['tablename'];
                $acne[$tableName] = function () use ($platform, $prefix) {
                    return new Table\ContentType($platform, $prefix);
                };
            }

            return $acne;
        };

        // Schemas (empty) of the extension tables
        $app['schema.extension_tables'] = function (Application $app) {
            return new Container([]);
        };

        // Combined schemas of all Bolt tables.
        $app['schema.tables'] = function (Application $app) {
            $acne = new Container();

            foreach ($app['schema.base_tables']->keys() as $baseName) {
                $acne[$baseName] = function () use ($app, $baseName) {
                    return $app['schema.base_tables'][$baseName];
                };
            }

            foreach ($app['schema.content_tables']->keys() as $baseName) {
                $acne[$baseName] = function () use ($app, $baseName) {
                    return $app['schema.content_tables'][$baseName];
                };
            }

            foreach ($app['schema.extension_tables']->keys() as $baseName) {
                $acne[$baseName] = function () use ($app, $baseName) {
                    return $app['schema.extension_tables'][$baseName];
                };
            }

            return $acne;
        };

        $app['schema.builder'] = function ($app) {
            return new Container([
                'base'       => function () use ($app) {
                    return new Builder\BaseTables(
                        $app['db'],
                        $app['schema'],
                        $app['schema.base_tables'],
                        $app['schema.charset'],
                        $app['schema.collate'],
                        $app['logger.system'],
                        $app['logger.flash']
                    );
                },
                'content'    => function () use ($app) {
                    return new Builder\ContentTables(
                        $app['db'],
                        $app['schema'],
                        $app['schema.content_tables'],
                        $app['schema.charset'],
                        $app['schema.collate'],
                        $app['logger.system'],
                        $app['logger.flash']
                    );
                },
                'extensions' => function () use ($app) {
                    return new Builder\ExtensionTables(
                        $app['db'],
                        $app['schema'],
                        $app['schema.extension_tables'],
                        $app['schema.charset'],
                        $app['schema.collate'],
                        $app['logger.system'],
                        $app['logger.flash']
                    );
                },
            ]);
        };

        $app['schema.timer'] = function ($app) {
            return new Timer($app['filesystem']->getFile('cache://dbcheck.ts'));
        };

        $app['schema.comparator.factory'] = $app->protect(
            function () use ($app) {
                $platforms = [
                    'mysql'      => Comparison\MySql::class,
                    'postgresql' => Comparison\PostgreSql::class,
                    'sqlite'     => Comparison\Sqlite::class,
                ];
                $platformName = $app['db']->getDatabasePlatform()->getName();

                return $platforms[$platformName];
            }
        );

        $app['schema.comparator'] = function ($app) {
            $comparator = $app['schema.comparator.factory']();

            return new $comparator(
                $app['db'],
                $app['schema.prefix'],
                $app['logger.system']
            );
        };
    }
}
