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
use GuzzleHttp\Psr7\Uri;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Because screw PHP core.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['session'] = $app->share(
            function ($app) {
                return new Session(
                    $app['session.storage'],
                    $app['session.bag.attribute'],
                    $app['session.bag.flash']
                );
            }
        );

        $app['session.storage'] = $app->share(
            function ($app) {
                $options = $app['session.options_bag'];

                $handler = $app['session.handler_factory']($options['save_handler'], $options);

                return new SessionStorage(
                    $options,
                    $handler,
                    $app['session.generator'],
                    $app['session.serializer']
                );
            }
        );

        $app['session.listener'] = $app->share(
            function ($app) {
                return new SessionListener($app['session'], $app['session.options_bag']);
            }
        );

        $this->registerOptions($app);

        $this->registerHandlers($app);

        $app['session.generator'] = $app->share(
            function () use ($app) {
                return new RandomGenerator($app['randomgenerator'], $app['session.generator.bytes_length']);
            }
        );
        $app['session.generator.bytes_length'] = 32;

        $app['session.serializer'] = $app->share(
            function () {
                return new NativeSerializer();
            }
        );

        $app['session.bag.attribute'] = function () {
            return new AttributeBag();
        };

        $app['session.bag.flash'] = function () {
            return new FlashBag();
        };

        $app['session.bag.metadata'] = function () {
            return new MetadataBag();
        };

        $this->configure($app);
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['session.listener']);
    }

    /**
     * This should be the only place in this class that is specific to bolt.
     *
     * @param Application $app
     */
    public function configure(Application $app)
    {
        $app['session.options'] = [
            'name'            => 'bolt_session_',
            'restrict_realm'  => true,
            'save_handler'    => 'filesystem',
            'save_path'       => 'cache://.sessions',
            'cookie_lifetime' => $app['config']->get('general/cookies_lifetime'),
            'cookie_path'     => $app['resources']->getUrl('root'),
            'cookie_domain'   => $app['config']->get('general/cookies_domain'),
            'cookie_secure'   => $app['config']->get('general/enforce_ssl'),
            'cookie_httponly' => true,
        ];
    }

    protected function registerOptions(Application $app)
    {
        $app['session.options'] = [];
        $app['session.options.import_from_ini'] = true;

        $app['session.options_bag'] = $app->share(
            function () use ($app) {
                /*
                 * This does two things.
                 * 1) Merges options together. Precedence is as follows:
                 *    - Options from session.options
                 *    - Options from ini (if enabled with "session.options.import_from_ini")
                 *    - Options hardcoded below
                 * 2) Converts options to an OptionsBag instance
                 */
                $defaults = [
                    'save_handler'    => 'files',
                    'save_path'       => '/tmp',
                    'name'            => 'PHPSESSID',
                    'lazy_write'      => true,
                    //'auto_start' => false,
                    //'serialize_handler' => null,
                    'gc_probability'  => 1,
                    'gc_divisor'      => 1000,
                    'gc_maxlifetime'  => 1440,
                    //'referer_check' => '',
                    //'use_strict_mode' => false,
                    'cookie_lifetime' => 0,
                    'cookie_path'     => '/',
                    'cookie_domain'   => null,
                    'cookie_secure'   => false,
                    'cookie_httponly' => false,
                    // TODO Do started native sessions force "nocache" header in response?
                    // We don't have a way to force that, should we?
                    //'cache_limiter' => 'nocache',
                    //'cache_expire'  => 180,
                    'restrict_realm'  => false,
                ];

                $options = new OptionsBag($defaults);

                if ($app['session.options.import_from_ini']) {
                    foreach ($options as $key => $value) {
                        $options[$key] = ini_get('session.' . $key);
                    }
                }

                if (isset($app['session.storage.options'])) {
                    $options->add($app['session.storage.options']);
                }

                $options->add($app['session.options']);

                return $options;
            }
        );
    }

    protected function registerHandlers(Application $app)
    {
        $app['session.handler_factory'] = $app->protect(
            function ($handler, $options) use ($app) {
                $key = 'session.handler_factory.' . $handler;
                if (isset($app[$key])) {
                    return $app[$key]($options);
                }
                throw new \RuntimeException("Unsupported handler type '$handler' specified");
            }
        );

        $this->registerFilesHandler($app);
        $this->registerFilesystemHandler($app);
        $this->registerMemcacheHandler($app);
        $this->registerRedisHandler($app);
    }

    protected function registerFilesHandler(Application $app)
    {
        $app['session.handler_factory.files'] = $app->protect(
            function ($options) use ($app) {
                return new FileHandler($options['save_path'], $app['logger.system']);
            }
        );
    }

    protected function registerFilesystemHandler(Application $app)
    {
        $app['session.handler_factory.filesystem'] = $app->protect(
            function ($options) use ($app) {
                $dir = $app['filesystem']->getDir($options['save_path']);

                return new FilesystemHandler($dir);
            }
        );
    }

    protected function registerMemcacheHandler(Application $app)
    {
        $app['session.handler_factory.backing_memcache'] = $app->protect(
            function ($connections) {
                $memcache = new \Memcache();

                foreach ($connections as $conn) {
                    $memcache->addServer(
                        $conn['host'] ?: 'localhost',
                        $conn['port'] ?: 11211,
                        $conn['persistent'] ?: false,
                        $conn['weight'] ?: 0,
                        $conn['timeout'] ?: 1
                    );
                }

                return $memcache;
            }
        );

        $app['session.handler_factory.memcache'] = $app->protect(
            function ($options, $key = 'memcache') use ($app) {
                $connections = $this->parseConnections($options, 'localhost', 11211);
                $memcache = $app['session.handler_factory.backing_' . $key]($connections);

                $handlerOptions = [];
                if (isset($options['expiretime'])) {
                    $handlerOptions['expiretime'] = $options['expiretime'];
                }
                if (isset($options['prefix'])) {
                    $handlerOptions['prefix'] = $options['prefix'];
                }

                if ($key === 'memcache') {
                    return new MemcacheSessionHandler($memcache, $handlerOptions);
                } else {
                    return new MemcachedSessionHandler($memcache, $handlerOptions);
                }
            }
        );

        $app['session.handler_factory.memcached'] = $app->protect(
            function ($options) use ($app) {
                return $app['session.handler_factory.memcache']($options, 'memcached');
            }
        );
    }

    protected function registerRedisHandler(Application $app)
    {
        $app['session.handler_factory.backing_redis'] = $app->protect(
            function ($connections) {
                if (class_exists('Redis')) {
                    $redis = new \Redis();
                    foreach ($connections as $conn) {
                        $params = [$conn['path'] ?: $conn['host'], $conn['port'], $conn['timeout'] ?: 0];
                        call_user_func_array([$redis, $conn['persistant'] ? 'pconnect' : 'connect'], $params);
                        if (!empty($conn['password'])) {
                            $redis->auth($conn['password']);
                        }
                        if ($conn['database'] > 0) {
                            $redis->select($conn['database']);
                        }
                        if (!empty($conn['prefix'])) {
                            $redis->setOption(\Redis::OPT_PREFIX, $conn['prefix']);
                        }
                    }
                } elseif (class_exists('Predis\Client')) {
                    $params = [];
                    $options = [];
                    foreach ($connections as $conn) {
                        $params[] = $conn;
                        if (!empty($conn['prefix'])) {
                            $options['prefix'] = $conn['prefix'];
                        }
                    }
                    $redis = new \Predis\Client($params, $options);
                } else {
                    throw new \RuntimeException('Neither Redis nor Predis\Client exist');
                }

                return $redis;
            }
        );

        $app['session.handler_factory.redis'] = $app->protect(
            function ($options) use ($app) {
                $connections = $this->parseConnections($options, 'localhost', 6379);
                $redis = $app['session.handler_factory.backing_redis']($connections);

                return new RedisHandler($redis, $options['gc_maxlifetime']);
            }
        );
    }

    protected function parseConnections($options, $defaultHost, $defaultPort)
    {
        if (isset($options['host']) || isset($options['port'])) {
            $options['connections'][] = $options;
        } elseif ($options['connection']) {
            $options['connections'][] = $options['connection'];
        }

        /** @var ParameterBag[] $toParse */
        $toParse = [];
        if (isset($options['connections'])) {
            foreach ((array) $options['connections'] as $alias => $conn) {
                if (is_string($conn)) {
                    $conn = ['host' => $conn];
                }
                $conn += [
                    'scheme' => 'tcp',
                    'host'   => $defaultHost,
                    'port'   => $defaultPort,
                ];
                $conn = new ParameterBag($conn);

                if ($conn->has('password')) {
                    $conn->set('pass', $conn->get('password'));
                    $conn->remove('password');
                }
                $conn->set('uri', Uri::fromParts($conn->all()));

                $toParse[] = $conn;
            }
        } elseif (isset($options['save_path'])) {
            foreach (explode(',', $options['save_path']) as $conn) {
                $conn = new ParameterBag($conn);
                $conn->set('uri', new Uri($conn));
                $toParse[] = $conn;
            }
        }

        $connections = [];
        foreach ($toParse as $conn) {
            /** @var Uri $uri */
            $uri = $conn->get('uri');

            $parts = explode(':', $uri->getUserInfo(), 2);
            $password = isset($parts[1]) ? $parts[1] : null;

            $connections[] = [
                'scheme'     => $uri->getScheme(),
                'host'       => $uri->getHost(),
                'port'       => $uri->getPort(),
                'path'       => $uri->getPath(),
                'alias'      => $conn->get('alias'),
                'prefix'     => $conn->get('prefix'),
                'password'   => $password,
                'database'   => $conn->get('database'),
                'persistent' => $conn->get('persistent'),
                'weight'     => $conn->get('weight'),
                'timeout'    => $conn->get('timeout'),
            ];
        }

        return $connections;
    }
}
