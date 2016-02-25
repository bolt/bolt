<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider\FormServiceProvider as SilexFormServiceProvider;
use Silex\ServiceProviderInterface;

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
    }

    public function boot(Application $app)
    {
    }
}
