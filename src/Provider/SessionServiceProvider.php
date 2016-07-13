<?php

namespace Bolt\Provider;

use Bolt\Session\Generator\RandomGenerator;
use Bolt\Session\Handler\FilesystemHandler;
use Bolt\Session\Handler\RedisHandler;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionListener;
use Bolt\Session\SessionStorage;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Predis\Client as Predis;

/**
 * @author Carson Full <carsonfull@gmail.com>
 * @author Daniel Wolf <danielrwolf5@gmail.com>
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['session.listener'] = $app->share(
            function ($app) {
                return new SessionListener($app['session'], $app['session.options_bag']);
            }
        );

        $app['session'] = $app->share(function (Application $app) {
            return new Session(
                $app['session.storage'],
                new AttributeBag(),
                new FlashBag()
            );
        });

        $app['session.storage'] = $app->share(function (Application $app) {
            return new SessionStorage(
                new OptionsBag(),
                $app['session.storage.handler'],
                new RandomGenerator(
                    $app['randomgenerator'],
                    $app['session.generator.bytes_length']
                ),
                new NativeSerializer(),
                $app['monolog'],
                new MetadataBag()
            );
        });

        if ($app->offsetExists('session.storage.handler') === false) {
            $app['session.storage.handler'] = $app->share(function (Application $app) {
                $storageConfigOption = $app['config']->get('general/session/handler', 'filesystem');

                if ($app['session.test'] === true or $storageConfigOption === 'null') {
                    $handler = new NullSessionHandler();

                } else if ($storageConfigOption === 'filesystem') {
                    $handler = new FilesystemHandler(
                        $app['filesystem']->getDir(
                            $app['config']->get('general/session/filesystem/dir', 'cache://.sessions')
                        )
                    );

                } else if ($storageConfigOption === 'files') {
                    $handler = new FilesystemHandler(
                        $app['filesystem']->getDir(
                            $app['config']->get('general/session/files/dir', 'cache://.sessions')
                        )
                    );

                } else if ($storageConfigOption === 'memcached') {
                    $memcached = new \Memcached();
                    $memcached->addServers(
                        $app['config']->get('general/session/memcached/servers', [
                            [
                                'host' => '127.0.0.1',
                                'port' => '11211'
                            ]
                        ])
                    );
                    $handler = new MemcachedSessionHandler($memcached, [
                        'prefix' => $app['config']->get('general/session/memcached/prefix', 'bolt_session_'),
                        'expiretime' => $app['config']->get('general/session/memcached/expire_time', 86400),
                    ]);

                } else if ($storageConfigOption === 'memcache') {
                    $memcache = new \Memcache();

                    $memcacheServers = $app['config']->get('general/session/memcached/servers', [
                        [
                            'host' => '127.0.0.1',
                            'port' => '11211',
                            'persistent' => false,
                            'weight' => 0
                        ]
                    ]);
                    foreach ($memcacheServers as $memcacheServer) {
                        $memcache->addserver(
                            $memcacheServer['host'],
                            $memcacheServer['port'],
                            $memcacheServer['persistent'],
                            $memcacheServer['weight']
                        );
                    }

                    $handler = new MemcacheSessionHandler($memcache, [
                        'prefix' => $app['config']->get('general/session/memcached/prefix', 'bolt_session_'),
                        'expiretime' => $app['config']->get('general/session/memcached/expire_time', 86400),
                    ]);

                } else if ($storageConfigOption === 'native_file') {
                    $handler = new NativeFileSessionHandler(
                        $app['config']->get('general/session/native_file/dir', '/tmp')
                    );

                } else if ($storageConfigOption === 'native') {
                    $handler = new NativeSessionHandler();

                } else if ($storageConfigOption === 'predis') {
                    if (!class_exists('\Predis\Client')) {
                        throw new \RuntimeException('predis/predis composer package not installed');
                    }

                    $predisConnections = $app['config']->get('general/session/predis/connections', ['tcp://127.0.0.1']);
                    $predisOptions = [
                        'prefix' => $app['config']->get('general/session/predis/prefix', 'bolt_session:'),
                        'parameters' => $app['config']->get('general/session/predis/parameters', []),
                        'cluster' => $app['config']->get('general/session/predis/cluster', 'redis'),
                    ];
                    $predis = new Predis($predisConnections, $predisOptions);

                    $handler = new RedisHandler(
                        $predis,
                        $app['config']->get('general/session/predis/max_lifetime', 1440)
                    );

                } else if ($storageConfigOption === 'redis') {
                    if (!class_exists('\Redis')) {
                        throw new \RuntimeException('Redis extension not installed');
                    }

                    $redis = new \Redis();

                    $method = $app['config']->get('general/session/redis/persistent', false) ? 'pconnect' : 'connect';
                    $redis->$method(
                        $app['config']->get('general/session/redis/host', '127.0.0.1'),
                        $app['config']->get('general/session/redis/port', 6379)
                    );

                    if ($redisPassword = $app['config']->get('general/session/redis/password', false)) {
                        $redis->auth($redisPassword);
                    }

                    if ($redisDatabase = $app['config']->get('general/session/redis/database', false)) {
                        $redis->select($redisDatabase);
                    }

                    $redis->setOption(
                        \Redis::OPT_PREFIX,
                        $app['config']->get('general/session/redis/prefix', 'bolt_session:')
                    );

                    $handler = new RedisHandler($redis, $app['config']->get('general/session/redis/max_lifetime', 1440));

                } else {
                    throw new \RuntimeException("Unsupported session storage handler '$storageConfigOption' specified");
                }

                return $handler;
            });
        }

    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
