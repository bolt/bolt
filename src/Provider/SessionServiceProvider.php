<?php

namespace Bolt\Provider;

use Bolt\Helpers\Deprecated;
use Bolt\Session\Generator\NativeGenerator;
use Bolt\Session\Handler\Factory\MemcachedFactory;
use Bolt\Session\Handler\Factory\MemcacheFactory;
use Bolt\Session\Handler\Factory\PredisFactory;
use Bolt\Session\Handler\Factory\RedisFactory;
use Bolt\Session\Handler\FileHandler;
use Bolt\Session\Handler\FilesystemHandler;
use Bolt\Session\Handler\MemcachedHandler;
use Bolt\Session\Handler\MemcacheHandler;
use Bolt\Session\Handler\RedisHandler;
use Bolt\Session\IniBag;
use Bolt\Session\OptionsBag;
use Bolt\Session\Serializer\NativeSerializer;
use Bolt\Session\SessionListener;
use Bolt\Session\SessionStorage;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Because screw PHP core.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
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
                return new SessionStorage(
                    $app['session.options_bag'],
                    $app['session.handler'],
                    $app['session.generator'],
                    $app['session.serializer']
                );
            }
        );

        $app['session.handler'] = $app->share(
            function ($app) {
                $options = $app['session.options_bag'];

                return $app['session.handler_factory']($options['save_handler'], $options);
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
                return new NativeGenerator($app['session.generator.bytes_length']);
            }
        );
        $app['session.generator.bytes_length'] = $app->share(
            function ($app) {
                $options = $app['session.options_bag'];

                return $options->getInt('sid_length', 32);
            }
        );

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

    /**
     * {@inheritdoc}
     */
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
        $app['session.options'] = function () use ($app) {
            $config = $app['config'];

            return $config->get('general/session', []) + [
                'name'            => 'bolt_session',
                'restrict_realm'  => true,
                'cookie_lifetime' => $config->get('general/cookies_lifetime'),
                'cookie_domain'   => $config->get('general/cookies_domain'),
                'cookie_secure'   => $config->get('general/enforce_ssl'),
                'cookie_httponly' => true,
            ];
        };
    }

    /**
     * @param Application $app
     */
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
                 *
                 * These defaults are limited to those that are useful and secure.
                 */
                $defaults = [
                    'save_handler'    => 'files',
                    'save_path'       => '/tmp',
                    'name'            => 'PHPSESSID',
                    'lazy_write'      => true,
                    'gc_probability'  => 1,
                    'gc_divisor'      => 1000,
                    'gc_maxlifetime'  => 1440,
                    'cookie_lifetime' => 0,
                    'cookie_path'     => '/',
                    'cookie_domain'   => null,
                    'cookie_secure'   => false,
                    'cookie_httponly' => false,
                    'sid_length'      => 32,

                    'restrict_realm'  => false,
                ];

                $options = new OptionsBag($defaults);

                if ($app['session.options.import_from_ini']) {
                    $ini = new IniBag('session');
                    foreach ($options as $key => $value) {
                        if (!$ini->has($key)) {
                            continue;
                        }

                        if (is_int($value)) {
                            $value = $ini->getInt($key);
                        } elseif (is_bool($value)) {
                            $value = $ini->getBoolean($key);
                        } else {
                            $value = $ini->get($key);
                        }

                        $options[$key] = $value;
                    }
                }

                // @deprecated backwards compatibility:
                if (isset($app['session.storage.options'])) {
                    $options->add($app['session.storage.options']);
                }

                // PHP's native C code accesses filesystem with different permissions than userland code.
                // If php.ini is using the default (files) handler, use ours instead to prevent this problem.
                if ($options->get('save_handler') === 'files') {
                    $options->set('save_handler', 'filesystem');
                    $options->set('save_path', 'cache://.sessions');
                }

                $overrides = $app['session.options'];

                // Don't let save_path for different save_handler bleed in.
                if (isset($overrides['save_handler']) && $overrides['save_handler'] !== $options['save_handler']) {
                    $options->remove('save_path');
                }

                $options->add($overrides);

                return $options;
            }
        );
    }

    /**
     * @param Application $app
     */
    protected function registerHandlers(Application $app)
    {
        $app['session.handler_factory'] = $app->protect(
            function ($handler, OptionsBag $options) use ($app) {
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

    /**
     * @param Application $app
     */
    protected function registerFilesHandler(Application $app)
    {
        $app['session.handler_factory.files'] = $app->protect(
            function ($options) use ($app) {
                return new FileHandler($options['save_path'], $app['logger.system']);
            }
        );
    }

    /**
     * @param Application $app
     */
    protected function registerFilesystemHandler(Application $app)
    {
        $app['session.handler_factory.filesystem'] = $app->protect(
            function ($options) use ($app) {
                $dir = $app['filesystem']->getDir($options['save_path']);

                return new FilesystemHandler($dir);
            }
        );
    }

    /**
     * @param Application $app
     */
    protected function registerMemcacheHandler(Application $app)
    {
        // @deprecated
        $app['session.handler_factory.backing_memcache'] = $app->protect(
            function (OptionsBag $options) {
                return (new MemcacheFactory())->create($options);
            }
        );

        $app['session.handler_factory.backing_memcached'] = $app->protect(
            function (OptionsBag $options) {
                return (new MemcachedFactory())->create($options);
            }
        );

        $app['session.handler_factory.memcache'] = $app->protect(
            function (OptionsBag $options, $key = 'memcache') use ($app) {
                if ($key === 'memcache') {
                    Deprecated::warn('"memcache" session handler', 3.3, 'Use "memcached" instead.');
                }

                $memcache = $app['session.handler_factory.backing_' . $key]($options);

                $handlerOptions = new OptionsBag($options->get('options', []));

                $memcacheOptions = [];
                if (isset($options['expiretime'])) {
                    Deprecated::warn('Specifying "expiretime" directly in session config', 3.3, 'Move it under the "options" key.');

                    $memcacheOptions['expiretime'] = $options['expiretime'];
                } elseif (isset($handlerOptions['expiretime'])) {
                    $memcacheOptions['expiretime'] = $handlerOptions['expiretime'];
                }
                if (isset($options['prefix'])) {
                    Deprecated::warn('Specifying "prefix" directly in session config', 3.3, 'Move it under the "options" key.');

                    $memcacheOptions['prefix'] = $options['prefix'];
                } elseif (isset($handlerOptions['prefix'])) {
                    $memcacheOptions['prefix'] = $handlerOptions['prefix'];
                }

                if ($key === 'memcache') {
                    return new MemcacheHandler($memcache, $memcacheOptions);
                }

                return new MemcachedHandler($memcache, $memcacheOptions);
            }
        );

        $app['session.handler_factory.memcached'] = $app->protect(
            function (OptionsBag $options) use ($app) {
                return $app['session.handler_factory.memcache']($options, 'memcached');
            }
        );
    }

    /**
     * @param Application $app
     */
    protected function registerRedisHandler(Application $app)
    {
        $app['session.handler_factory.backing_redis'] = $app->protect(
            function (OptionsBag $options) use ($app) {
                return (new RedisFactory())->create($options);
            }
        );

        $app['session.handler_factory.redis'] = $app->protect(
            function (OptionsBag $options) use ($app) {
                $redis = $app['session.handler_factory.backing_redis']($options);

                return new RedisHandler($redis, $options['gc_maxlifetime']);
            }
        );

        $app['session.handler_factory.backing_predis'] = $app->protect(
            function (OptionsBag $options) use ($app) {
                return (new PredisFactory())->create($options);
            }
        );

        $app['session.handler_factory.predis'] = $app->protect(
            function (OptionsBag $options) use ($app) {
                $redis = $app['session.handler_factory.backing_predis']($options);

                return new RedisHandler($redis, $options['gc_maxlifetime']);
            }
        );
    }
}
