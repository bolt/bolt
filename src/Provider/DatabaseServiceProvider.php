<?php

namespace Bolt\Provider;

use Bolt\EventListener\DoctrineListener;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Database provider.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['db'])) {
            $app->register(
                new \Silex\Provider\DoctrineServiceProvider(),
                [
                    'db.options' => $app['config']->get('general/database'),
                ]
            );
        }

        $app['db.config'] = $app->share(
            $app->extend(
                'db.config',
                function ($config) use ($app) {
                    $config->setFilterSchemaAssetsExpression($app['schema.tables_filter']);

                    return $config;
                }
            )
        );

        $app['db.doctrine_listener'] = $app->share(
            function ($app) {
                return new DoctrineListener($app['logger.system']);
            }
        );

        // For each database connection add this class as an event subscriber
        $app['dbs.event_manager'] = $app->share(
            $app->extend(
                'dbs.event_manager',
                function ($managers) use ($app) {
                    /** @var \Pimple $managers */
                    foreach ($managers->keys() as $name) {
                        /** @var \Doctrine\Common\EventManager $manager */
                        $manager = $managers[$name];
                        $manager->addEventSubscriber($app['db.doctrine_listener']);
                    }

                    return $managers;
                }
            )
        );

        $app['db.query_cache'] = $app->share(
            function ($app) {
                $cache = ($app['config']->get('general/caching/database') == true) ? $app['cache'] : new ArrayCache();

                return $cache;
            }
        );

        $app['db.query_cache_profile'] = $app->share(
            function ($app) {
                $lifetime = $app['config']->get('general/caching/duration') ?: 0;

                return new QueryCacheProfile($lifetime, 'bolt.db', $app['db.query_cache']);
            }
        );

        $app['db'] = $app->share(
            $app->extend(
                'db',
                function ($db) use ($app) {
                    $db->setQueryCacheProfile($app['db.query_cache_profile']);

                    return $db;
                }
            )
        );
    }

    public function boot(Application $app)
    {
    }
}
