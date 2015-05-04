<?php

namespace Bolt\Provider;

use Bolt\Authentication;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthenticationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['authentication'] = $app->share(
            function ($app) {

                $authentication = new Authentication($app);

                return $authentication;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
