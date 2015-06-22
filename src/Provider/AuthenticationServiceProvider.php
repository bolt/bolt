<?php

namespace Bolt\Provider;

use Bolt\AccessControl\Authentication;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthenticationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['authentication'] = $app->share(
            function ($app) {
                $repo = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $authentication = new Authentication($app, $repo);

                return $authentication;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
