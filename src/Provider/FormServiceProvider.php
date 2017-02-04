<?php

namespace Bolt\Provider;

use Bolt\Form;
use Bolt\Form\Validator\Constraints\ExistingEntityValidator;
use Silex\Application;
use Silex\Provider\FormServiceProvider as SilexFormServiceProvider;
use Pimple\ServiceProviderInterface;
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

        $app['form.csrf_provider'] = 
            function ($app) {
                return $app['csrf'];
            }
        ;

        $app['form.extensions'] = 
            $app->extend(
                'form.extensions',
                function ($extensions, $app) {
                    $extensions[] = new Form\BoltExtension($app);

                    return $extensions;
                }
            )
        ;

        $app['form.validator.existing_entity'] = 
            function ($app) {
                return new ExistingEntityValidator($app['storage']);
            }
        ;

        $app['validator.validator_service_ids'] += [
            ExistingEntityValidator::class => 'form.validator.existing_entity'
        ];


        $app['csrf'] = 
            function ($app) {
                return new CsrfTokenManager($app['csrf.generator'], $app['csrf.storage']);
            }
        ;

        $app['csrf.generator'] = 
            function ($app) {
                return new UriSafeTokenGenerator($app['csrf.generator.entropy']);
            }
        ;
        $app['csrf.generator.entropy'] = 256;

        $app['csrf.storage'] = 
            function ($app) {
                return new SessionTokenStorage($app['session']);
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
