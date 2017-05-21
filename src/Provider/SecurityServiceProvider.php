<?php

namespace Bolt\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\SecurityServiceProvider as SilexSecurityServiceProvider;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
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
    public function register(Container $app)
    {
        $app->register(new SilexSecurityServiceProvider());

        $app['security.firewalls'] = function ($app) {
            $boltPath = $app['config']->get('general/branding/path');

            return  [
                'login_path' => [
                    'pattern'   => '^' . $boltPath . '/login$',
                    'anonymous' => true,
                ],
                'bolt' => [
                    'pattern'  => '^' . $boltPath,
                    'security' => true,
                    'anonymous' => true,
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
        };

        $app['security.access_rules'] = function ($app) {
            $boltPath = $app['config']->get('general/branding/path');

            return [
                ['^' . $boltPath . '/login$', AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY],
            ];
        };

        // Use lazy url generator
        $app['security.http_utils'] = function ($app) {
            return new HttpUtils($app['url_generator.lazy'], $app['url_matcher']);
        };
    }
}
