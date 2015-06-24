<?php

namespace Bolt\Provider;

use Bolt\AccessControl;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthenticationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['authentication.login'] = $app->share(
            function ($app) {
                $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $repoUser = $app['storage']->getRepository('Bolt\Storage\Entity\Users');

                $login = new AccessControl\Login($app, $repoAuth, $repoUser);

                return $login;
            }
        );

        $app['authentication.logout'] = $app->share(
            function ($app) {
                $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $logout = new AccessControl\Logout($repoAuth, $app['session'], $app['logger.flash']);

                return $logout;
            }
        );

        $app['authentication.password'] = $app->share(
            function ($app) {
                $password = new AccessControl\Password($app);

                return $password;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
