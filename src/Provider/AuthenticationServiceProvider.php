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
                $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $repoUser = $app['storage']->getRepository('Bolt\Storage\Entity\Users');
                $authentication = new Authentication($app, $repoAuth, $repoUser);

                return $authentication;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
