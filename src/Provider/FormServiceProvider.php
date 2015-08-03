<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider\FormServiceProvider as SilexFormServiceProvider;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Register form services
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['form.factory'])) {
            $app->register(new SilexFormServiceProvider());
        }

        $app['form.csrf_provider'] = $app->share(function ($app) {
            $storage = new SessionTokenStorage($app['sessions']['csrf']);
            return new CsrfTokenManager(null, $storage);
        });
    }

    public function boot(Application $app)
    {
    }
}
