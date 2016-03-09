<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider\FormServiceProvider as SilexFormServiceProvider;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
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

        $app['form.csrf_provider'] = $app->share(
            function ($app) {
                return $app['csrf'];
            }
        );

        $app['csrf'] = $app->share(
            function ($app) {
                return new CsrfTokenManager($app['csrf.generator'], $app['csrf.storage']);
            }
        );

        $app['csrf.generator'] = $app->share(
            function ($app) {
                return new UriSafeTokenGenerator($app['csrf.generator.entropy']);
            }
        );
        $app['csrf.generator.entropy'] = 256;

        $app['csrf.storage'] = $app->share(
            function ($app) {
                return new SessionTokenStorage($app['session']);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
