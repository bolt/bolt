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
                new AttributeBag(),
                new FlashBag()
            );
        });

        $app['session.listener'] = $app->share(
            function ($app) {
                return new SessionListener($app['session'], $app['session.options_bag']);
            }
        );

        $app['session.storage'] = $app->share(function (Application $app) {
            return new SessionStorage(
                $app['session.storage.options'],
                $app['session.storage.handler'],
                $app['session.storage.random_generator'],
                $app['session.serializer'],
                $app['monolog'],
                new MetadataBag()
            );
        });

        $app['session.storage.handler'] = $app->share(function (Application $app) {
            $handler = $app['session.storage.options']->get('dir');

            if (!isset($app['session.storage.handler.' . $handler])) {
                throw new \RuntimeException("Invalid storage handler '$handler' specified.")
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
        $this->app['session.storage.handler.filesystem'] = $this->app->share(function (Application $app) {
            // todo decouple the filesystem class from this provider?
            return new FilesystemHandler(
                $app['filesystem']->getDir(
                    $app['session.storage.options']->get('dir')
                )
            );
        });

        $this->app['session.storage.handler.file'] = $this->app->share(function (Application $app) {
            $logger = isset($app['monolog']) ? $app['monolog'] : null;
            $fileSystem = null;
            if (isset($app['filesystem'])) {
                $fileSystem = $app['filesystem']->getDir(
                    $app['session.storage.options']->get('dir')
                );
            }

            return new FileHandler($app['session.storage.options']->get('files/dir'), $logger, $fileSystem);
        });

        $this->app['session.storage.handler.memcached'] = $this->app->share(function (Application $app) {
            $memcached = new \Memcached();
            $memcached->addServers($app['session.storage.options']->get('connections'));

            return new MemcachedSessionHandler($memcached, [
                'prefix' => $app['session.storage.options']->get('prefix'),
                'expiretime' => $app['session.storage.options']->get('lifetime'),
            ]);
        });

        $this->app['session.storage.handler.memcache'] = $this->app->share(function (Application $app) {
            $memcache = new \Memcache();

            $memcacheServers = $app['session.storage.options']->get('connections');
            foreach ($memcacheServers as $memcacheServer) {
                $memcache->addserver(
                    $memcacheServer['host'],
                    $memcacheServer['port'],
                    $memcacheServer['persistent'],
                    $memcacheServer['weight']
                );
            }

            return new MemcacheSessionHandler($memcache, [
                'prefix' => $app['session.storage.options']->get('prefix'),
                'expiretime' => $app['session.storage.options']->get('lifetime'),
            ]);

        });

        $this->app['session.storage.handler.native_file'] = $this->app->share(function (Application $app) {
            return new NativeFileSessionHandler($app['session.storage.options']->get('dir'));
        });

        $this->app['session.storage.handler.native'] = $this->app->share(function (Application $app) {
            return new NativeSessionHandler();
        });

        $this->app['session.storage.handler.predis'] = $this->app->share(function (Application $app) {
            if (!class_exists('\Predis\Client')) {
                throw new \RuntimeException('predis/predis composer package not installed');
            }

            $predisConnections = $app['session.storage.options']->get('connections');
            $predisOptions = [
                'prefix' => $app['session.storage.options']->get('prefix'),
                'parameters' => $app['session.storage.options']->get('parameters'),
                'cluster' => $app['session.storage.options']->get('cluster'),
            ];
            $predis = new Predis($predisConnections, $predisOptions);

            $handler = new RedisHandler($predis, $app['session.storage.options']->get('lifetime'));
        });

        $this->app['session.storage.handler.redis'] = $this->app->share(function (Application $app) {
            if (!class_exists('\Redis')) {
                throw new \RuntimeException('Redis extension not installed');
            }

            $redis = new \Redis();

            $method = $app['session.storage.options']->get('persistent') ? 'pconnect' : 'connect';
            $redis->$method(
                $app['session.storage.options']->get('connections')[0]['host'],
                $app['session.storage.options']->get('connections')[0]['port']
            );

            if ($redisPassword = $app['session.storage.options']->get('password')) {
                $redis->auth($redisPassword);
            }

            if ($redisDatabase = $app['session.storage.options']->get('database')) {
                $redis->select($redisDatabase);
            }

            $redis->setOption(\Redis::OPT_PREFIX, $app['session.storage.options']->get('prefix'));

            $handler = new RedisHandler($redis, $app['session.storage.options']->get('lifetime'));
        });
    }

    /**
     * @return void
     */
    protected function registerOptions()
    {
        $this->app['session.storage.options'] = $this->app->share(function (Application $app) {
            $optionsBag = new OptionsBag();

            // defaults
            $optionsBag->add([
                'dir' => 'cache://.sessions',
                'prefix' => 'bolt_session_',
                'lifetime' => 86400,
                'connections' => [
                    [
                        'host' => '127.0.0.1',
                        'port' => '11211'
                    ]
                ],
                'parameters' => [],
                'cluster' => 'redis',
                'persistent' => false,
                'password' => false,
                'database' => 0,
            ]);

            return $optionsBag;
        });
    }
}
