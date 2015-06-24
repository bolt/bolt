<?php

namespace Bolt\Provider;

use Bolt\AccessControl;
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
                $cookieOptions = [
                    'remoteaddr'   => $app['config']>get('general/cookies_use_remoteaddr', true),
                    'browseragent' => $app['config']->get('general/cookies_use_browseragent', false),
                    'httphost'     => $app['config']->get('general/cookies_use_httphost', true),
                ];

                $tracker= new AccessControl\AccessChecker(
                    $repoAuth,
                    $repoUser,
                    $cookieOptions,
                    $app['session'],
                    $app['logger.flash'],
                    $app['logger.system']
                );

                return $tracker;
            }
        );

        $app['authentication.login'] = $app->share(
            function ($app) {
                $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $repoUser = $app['storage']->getRepository('Bolt\Storage\Entity\Users');

                $login = new AccessControl\Login(
                    $repoAuth,
                    $repoUser,
                    $app['session'],
                    $app['logger.flash'],
                    $app['logger.system'],
                    $app['randomgenerator'],
                    $app['config']->get('general/cookies_lifetime', 1209600)
                );

                return $login;
            }
        );

        $app['authentication.logout'] = $app->share(
            function ($app) {
                $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $logout = new AccessControl\Logout(
                    $repoAuth,
                    $app['session'],
                    $app['logger.flash']
                );

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
