<?php

namespace Bolt\Provider;

use Bolt\Session\Bridge;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Session service provider.
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
        if (!isset($app['session'])) {
            $app->register(new Bridge\Silex1\SessionServiceProvider());
        }

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

        $app['session.options_bag'] = $app->share(
            $app->extend(
                'session.options_bag',
                function ($options) {
                    // PHP's native C code accesses filesystem with different permissions than userland code.
                    // If php.ini is using the default (files) handler, use ours instead to prevent this problem.
                    if ($options['save_handler'] === 'files') {
                        $options['save_handler'] = 'filesystem';
                        $options['save_path'] = 'cache://.sessions';
                    }

                    return $options;
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
