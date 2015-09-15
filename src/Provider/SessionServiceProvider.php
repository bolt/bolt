<?php

namespace Bolt\Provider;

use Bolt\Session\CookiePathRestrictionListener;
use Bolt\Session\Generator\RandomGenerator;
use Bolt\Session\Handler\FileHandler;
use Bolt\Session\Handler\RedisHandler;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionListener;
use Bolt\Session\SessionStorage;
use GuzzleHttp\Url;
use Silex\Application;
use Silex\ServiceProviderInterface;
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
        $this->registerSessions($app);
        $this->registerListeners($app);
        $this->registerOptions($app);
        $this->registerHandlers($app);

        $app['session.storage.generator'] = $app->share(function () use ($app) {
            return new RandomGenerator($app['randomgenerator']);
        });

        $app['session.storage.serializer'] = $app->share(function () {
            return new NativeSerializer();
        });

        $app['session.bag.attribute'] = function () {
            return new AttributeBag();
        };

        $app['session.bag.flash'] = function () {
            return new FlashBag();
        };

        $app['session.bag.metadata'] = function () {
            return new MetadataBag();
        };
    }

    public function registerSessions(Application $app)
    {
        $app['sessions'] = $app->share(function () use ($app) {
            $app['sessions.options.initializer']();

            $sessions = new \Pimple();
            foreach ($app['sessions.options'] as $name => $options) {
                $sessions[$name] = $app->share(function () use ($options, $app) {
                    return $app['session.factory']($options);
                });
            }

            return $sessions;
        });

        $app['session.factory'] = $app->protect(function ($options) use ($app) {
            return new Session(
                $app['session.storage.factory']($options),
                $app['session.bag.attribute'],
                $app['session.bag.flash']
            );
        });

        $app['session.storage.factory'] = $app->protect(function ($options) use ($app) {
            return new SessionStorage(
                $options,
                $app['session.storage.handler.factory']($options['save_handler'], $options),
                $app['session.storage.generator'],
                $app['session.storage.serializer']
            );
        });

        $app['session'] = $app->share(function ($app) {
            // Sessions needs to be called first so sessions.default is initialized
            $sessions = $app['sessions'];
            return $sessions[$app['sessions.default']];
        });
    }

    public function registerListeners(Application $app)
    {
        $app['sessions.listener'] = $app->share(function () use ($app) {
            $app['sessions.options.initializer']();

            $listeners = new \Pimple();
            foreach ($app['sessions']->keys() as $name) {
                $setToRequest = $name === $app['sessions.default'];
                $listeners[$name] = $app->share(function () use ($app, $name, $setToRequest) {
                    $session = $app['sessions'][$name];
                    $options = $app['sessions.options'][$name];
                    return $app['session.listener.factory']($session, $options, $setToRequest);
                });
            }

            return $listeners;
        });

        $app['session.listener.factory'] = $app->protect(function ($session, $options, $setToRequest = false) use ($app) {
            return new SessionListener($session, $options, $setToRequest);
        });

        $app['session.listener'] = $app->share(function ($app) {
            return $app['session.listeners'][$app['sessions.default']];
        });

        $app['session.cookie_path_restriction_listener.factory'] = $app->protect(function ($options) {
            return new CookiePathRestrictionListener($options);
        });
    }

    protected function registerOptions(Application $app)
    {
        $app['session.default_options'] = [];
        $app['session.options.import_from_ini'] = true;

        $app['sessions.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;

            if ($initialized) {
                return;
            }
            $initialized = true;

            /*
             * Ok this does several things.
             * 1) Merges options together for each session. Precedence is as follows:
             *    - Options from individual session
             *    - Options from "session.default_options"
             *    - Options from ini (if enabled with "session.options.import_from_ini")
             *    - Options hardcoded below
             * 2) Converts "session.options" shortcut to sessions.options['default']
             * 3) Sets "sessions.default" value to first session key in "sessions.options"
             * 4) Converts options for each session to an OptionsBag instance
             */
            $actualDefaults = [
                'save_handler'    => 'files',
                'save_path'       => '/tmp',
                'name'            => 'PHPSESSID',
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
            ];

            if (isset($app['session.options.import_from_ini']) && $app['session.options.import_from_ini']) {
                foreach ($actualDefaults as $key => $value) {
                    $actualDefaults[$key] = ini_get('session.' . $key);
                }
            }
            $app['session.default_options'] = array_replace($actualDefaults, $app['session.default_options']);

            // Maintain BC for "session.storage.options"
            if (isset($app['session.storage.options'])) {
                $app['session.default_options'] = array_replace($app['session.default_options'], $app['session.storage.options']);
            }

            if (!isset($app['sessions.options'])) {
                $app['sessions.options'] = [
                    'default' => isset($app['session.options']) ? $app['session.options'] : [],
                ];
            }

            $options = [];
            foreach ($app['sessions.options'] as $name => $opts) {
                if (!isset($app['sessions.default'])) {
                    $app['sessions.default'] = $name;
                }
                $opts = array_replace($app['session.default_options'], (array) $opts);
                $options[$name] = new OptionsBag($opts);
            }
            $app['sessions.options'] = $options;
        });
    }

    protected function registerHandlers(Application $app)
    {
        $app['session.storage.handler.factory'] = $app->protect(function ($handler, $options) use ($app) {
            $key = 'session.storage.handler.factory.' . $handler;
            if (isset($app[$key])) {
                return $app[$key]($options);
            }
            throw new \RuntimeException("Unsupported handler type '$handler' specified");
        });

        $app['session.storage.handler.factory.files'] = $app->protect(function ($options) use ($app) {
            return new FileHandler($options['save_path'], $app['logger.system']);
        });

        $this->registerMemcacheHandler($app);
        $this->registerRedisHandler($app);
    }

    public function boot(Application $app)
    {
        $listeners = $app['sessions.listener'];
        foreach ($listeners->keys() as $name) {
            $app['dispatcher']->addSubscriber($listeners[$name]);
        }
        foreach ($app['sessions.options'] as $options) {
            /** @var $options OptionsBag */
            if ($options->getBoolean('cookie_restrict_path')) {
                $listener = $app['session.cookie_path_restriction_listener.factory']($options);
                $app['dispatcher']->addSubscriber($listener);
            }
        }
    }

    protected function registerMemcacheHandler(Application $app)
    {
        $app['session.storage.handler.factory.backing_memcache'] = $app->protect(function ($connections) {
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
        });

        $app['session.storage.handler.factory.memcache'] = $app->protect(
            function ($options, $key = 'memcache') use ($app) {
                $connections = $this->parseConnections($options, 'localhost', 11211);
                $memcache = $app['session.storage.handler.factory.backing_' . $key]($connections);

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

        $app['session.storage.handler.factory.memcached'] = $app->protect(function ($options) use ($app) {
            return $app['session.storage.handler.factory.memcache']($options, 'memcached');
        });
    }

    protected function registerRedisHandler(Application $app)
    {
        $app['session.storage.handler.factory.backing_redis'] = $app->protect(function ($connections) {
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
        });

        $app['session.storage.handler.factory.redis'] = $app->protect(function ($options) use ($app) {
            $connections = $this->parseConnections($options, 'localhost', 6379);
            $redis = $app['session.storage.handler.factory.backing_redis']($connections);

            return new RedisHandler($redis, $options['gc_maxlifetime']);
        });
    }

    protected function parseConnections($options, $defaultHost, $defaultPort)
    {
        if (isset($options['host']) || isset($options['port'])) {
            $options['connections'][] = $options;
        }

        /** @var Url[] $toParse */
        $toParse = [];
        if (isset($options['connections'])) {
            foreach ((array) $options['connections'] as $alias => $conn) {
                if (is_string($conn)) {
                    $conn = ['host' => $conn];
                }
                $scheme = isset($conn['scheme']) ? $conn['scheme'] : 'tcp';
                $host = isset($conn['host']) ? $conn['host'] : $defaultHost;
                $url = new Url($scheme, $host);
                $url->setPort(isset($conn['port']) ? $conn['port'] : $defaultPort);
                if (isset($conn['path'])) {
                    $url->setPath($conn['path']);
                }
                if (isset($conn['password'])) {
                    $url->setPassword($conn['password']);
                }
                $url->getQuery()->replace($conn);
                $toParse[] = $url;
            }
        } elseif (isset($options['save_path'])) {
            foreach (explode(',', $options['save_path']) as $conn) {
                $toParse[] = Url::fromString($conn);
            }
        }

        $connections = [];
        foreach ($toParse as $url) {
            $connections[] = [
                'scheme'     => $url->getScheme(),
                'host'       => $url->getHost(),
                'port'       => $url->getPort(),
                'path'       => $url->getPath(),
                'alias'      => $url->getQuery()->get('alias'),
                'prefix'     => $url->getQuery()->get('prefix'),
                'password'   => $url->getPassword(),
                'database'   => $url->getQuery()->get('database'),
                'persistent' => $url->getQuery()->get('persistent'),
                'weight'     => $url->getQuery()->get('weight'),
                'timeout'    => $url->getQuery()->get('timeout'),
            ];
        }

        return $connections;
    }
}
