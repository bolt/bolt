<?php

namespace Bolt\Provider;

use Bolt\Form;
use Bolt\Form\Validator\Constraints\ExistingEntityValidator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider as SilexFormServiceProvider;

/**
 * Register form services.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (!isset($app['form.factory'])) {
            $app->register(new SilexFormServiceProvider());
        }

        $app['form.extensions'] = $app->extend(
            'form.extensions',
            function ($extensions, $app) {
                $extensions[] = new Form\BoltExtension($app);

                return $extensions;
            }
        );

        $app['form.validator.existing_entity'] = function ($app) {
            return new ExistingEntityValidator($app['storage']);
        };

        $app['validator.validator_service_ids'] += [
            ExistingEntityValidator::class => 'form.validator.existing_entity',
        ];
    }
}
