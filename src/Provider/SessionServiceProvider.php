<?php

namespace Bolt\Provider;

use Bolt\Session\Generator\RandomGenerator;
use Bolt\Session\Handler\FileHandler;
use Bolt\Session\Handler\FilesystemHandler;
use Bolt\Session\Handler\RedisHandler;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionListener;
use Bolt\Session\SessionStorage;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
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
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app)
    {
        $this->app = $app;

        $this->registerOptions();
        $this->registerHandlers();

        $app['session'] = $app->share(function (Application $app) {
            return new Session(
                $app['session.storage'],
                $app['session.bag.attribute'],
                $app['session.bag.flash']
            );
        });

        $app['session.listener'] = $app->share(
            function ($app) {
                return new SessionListener($app['session'], $app['session.bag.options']);
            }
        );

        $app['session.storage'] = $app->share(function (Application $app) {
            return new SessionStorage(
                $app['session.bag.options'],
                $app['session.storage.handler'],
                $app['session.storage.random_generator'],
                $app['session.serializer'],
                isset($app['monolog']) ? $app['monolog'] : null,
                $app['session.bag.metadata']
            );
        });

        $app['session.storage.handler'] = $app->share(function (Application $app) {
            $handler = $app['session.bag.options']->get('handler');

            if (!isset($app['session.storage.handler.' . $handler])) {
                throw new \RuntimeException("Invalid storage handler '$handler' specified.");
            }

            return $app['session.storage.handler.' . $handler];
        });

        $app['session.random_generator'] = $app->share(function (Application $app) {
            return new RandomGenerator(
                $app['randomgenerator'],
                $app['session.generator.bytes_length']
            );
        });

        $app['session.serializer'] = $app->share(function () {
            return new NativeSerializer();
        });

        $app['session.bag.flash'] = $app->share(function () {
            return new FlashBag();
        });

        $app['session.bag.attribute'] = $app->share(function () {
            return new AttributeBag();
        });

        $app['session.bag.metadata'] = $app->share(function () {
            return new MetadataBag();
        });
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }

    /**
     * @return void
     */
    protected function registerHandlers()
    {
        $this->app['session.storage.handler.null'] = $this->app->share(function () {
            return new NullSessionHandler();
        });

        $this->app['session.storage.handler.filesystem'] = $this->app->share(function (Application $app) {
            return new FilesystemHandler(
                $app['filesystem']->getDir(
                    $app['session.bag.options']->get('dir') ?: 'cache://.sessions'
                )
            );
        });

        $this->app['session.storage.handler.file'] = $this->app->share(function (Application $app) {
            $logger = isset($app['monolog']) ? $app['monolog'] : null;
            $fileSystem = isset($app['filesystem']) ? $app['filesystem'] : new Filesystem();

            return new FileHandler($app['session.bag.options']->get('dir') ?: '/tmp', $logger, $fileSystem);
        });

        $this->app['session.storage.handler.memcached'] = $this->app->share(function (Application $app) {
            $memcached = new \Memcached();

            $memcachedConnections = $app['session.bag.options']->get('connections');
            foreach ($memcachedConnections as $memcachedConnection) {
                $memcached->addServer(
                    $memcachedConnection['host'],
                    $memcachedConnection['port'] ?: 6379,
                    !empty($memcachedConnection['weight']) ? $memcachedConnection['weight'] : 0
                );
            }

            return new MemcachedSessionHandler($memcached, [
                'prefix' => $app['session.bag.options']->get('prefix'),
                'expiretime' => $app['session.bag.options']->get('lifetime'),
            ]);
        });

        $this->app['session.storage.handler.memcache'] = $this->app->share(function (Application $app) {
            $memcache = new \Memcache();

            $memcacheConnections = $app['session.bag.options']->get('connections');
            foreach ($memcacheConnections as $memcacheConnection) {
                $memcache->addserver(
                    $memcacheConnection['host'],
                    $memcacheConnection['port'] ?: 6379,
                    $app['session.bag.options']->getBoolean('persistent'),
                    !empty($memcachedConnection['weight']) ? $memcachedConnection['weight'] : 0
                );
            }

            return new MemcacheSessionHandler($memcache, [
                'prefix' => $app['session.bag.options']->get('prefix'),
                'expiretime' => $app['session.bag.options']->getInt('lifetime'),
            ]);
        });

        $this->app['session.storage.handler.native_file'] = $this->app->share(function (Application $app) {
            return new NativeFileSessionHandler($app['session.bag.options']->get('dir'));
        });

        $this->app['session.storage.handler.native'] = $this->app->share(function (Application $app) {
            return new NativeSessionHandler();
        });

        $this->app['session.storage.handler.predis'] = $this->app->share(function (Application $app) {
            if (!class_exists('\Predis\Client')) {
                throw new \RuntimeException('predis/predis composer package not installed');
            }

            $predisConnections = $app['session.bag.options']->get('connections');
            $predisOptions = [
                'prefix' => $app['session.bag.options']->get('prefix'),
                'parameters' => $app['session.bag.options']->get('parameters'),
                'cluster' => $app['session.bag.options']->get('cluster'),
            ];
            $predis = new Predis($predisConnections, $predisOptions);

            return new RedisHandler($predis, $app['session.bag.options']->getInt('lifetime'));
        });

        $this->app['session.storage.handler.redis'] = $this->app->share(function (Application $app) {
            if (!class_exists('\Redis')) {
                throw new \RuntimeException('Redis extension not installed');
            }

            $redis = new \Redis();

            $method = $app['session.bag.options']->get('persistent') ? 'pconnect' : 'connect';
            $connection = $app['session.bag.options']->get('connections')[0];
            $redis->$method(
                $connection['host'],
                $connection['port'] ?: 6379
            );

            if ($redisPassword = $app['session.bag.options']->get('password')) {
                $redis->auth($redisPassword);
            }

            if ($redisDatabase = $app['session.bag.options']->getInt('database')) {
                $redis->select($redisDatabase);
            }

            $redis->setOption(\Redis::OPT_PREFIX, $app['session.bag.options']->get('prefix'));

            return new RedisHandler($redis, $app['session.bag.options']->getInt('lifetime'));
        });
    }

    /**
     * @return void
     */
    protected function registerOptions()
    {
        $this->app['session.bag.options'] = $this->app->share(function () {
            return new OptionsBag([
                'handler' => 'filesystem',
                'cluster' => 'redis',
                'cookie_lifetime' => 0,
                'connections' => [
                    [
                        'host' => '127.0.0.1',
                        'port' => false
                    ]
                ],
                'database' => 0,
                'dir' => false,
                'gc_divisor' => 100,
                'gc_propability' => 1,
                'gc_maxlifetime' => 1440,
                'lazy_write' => false,
                'lifetime' => 86400,
                'name' => 'PHPSESSID',
                'parameters' => [],
                'password' => false,
                'persistent' => false,
                'prefix' => 'bolt_session_',
            ]);
        });
    }

}
