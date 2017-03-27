<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider\SecurityServiceProvider as SilexSecurityServiceProvider;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Bolt security service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app->register(new SilexSecurityServiceProvider());

        $app['security.firewalls'] = $app->share(
            function ($app) {
                $boltPath = $app['config']->get('general/branding/path');

                return  [
                    'login_path' => [
                        'pattern'   => '^' . $boltPath . '/login$',
                        'anonymous' => true,
                    ],
                    'bolt' => [
                        'pattern'  => '^' . $boltPath,
                        'security' => false,
                    ],
                    'default' => [
                        'pattern'   => '^/.*$',
                        'security'  => false,
                        'form'      => [
                            'login_path' => $boltPath . '/login',
                            'check_path' => $boltPath . '/login_check',
                        ],
                        'logout'    => [
                            'logout_path'        => $boltPath . '/logout',
                            'invalidate_session' => false,
                        ],
                    ],
                ];
            }
        );

        $app['security.access_rules'] = $app->share(
            function ($app) {
                $boltPath = $app['config']->get('general/branding/path');

                return [
                    ['^' . $boltPath . '/login$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ];
            }
        );

        // Use lazy url generator
        $app['security.http_utils'] = $app->share(function ($app) {
            return new HttpUtils($app['url_generator.lazy'], $app['url_matcher']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
