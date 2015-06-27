<?php

namespace Bolt\Provider;

use Bolt\AccessControl;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuthenticationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['authentication.cookie.options'] = $app->share(
            function () use ($app) {
                return [
                    'remoteaddr'   => $app['config']->get('general/cookies_use_remoteaddr', true),
                    'browseragent' => $app['config']->get('general/cookies_use_browseragent', false),
                    'httphost'     => $app['config']->get('general/cookies_use_httphost', true),
                    'lifetime'     => $app['config']->get('general/cookies_lifetime', 1209600),
                ];
            }
        );

        $app['authentication.hash.strength'] = $app->share(
            function () use ($app) {
                return max($app['config']->get('general/hash_strength'), 8);
            }
        );

        $app['authentication'] = $app->share(
            function ($app) {
                $repoAuth = $app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
                $repoUser = $app['storage']->getRepository('Bolt\Storage\Entity\Users');

                $tracker= new AccessControl\AccessChecker(
                    $repoAuth,
                    $repoUser,
                    $app['session'],
                    $app['logger.flash'],
                    $app['logger.system'],
                    $app['permissions'],
                    $app['randomgenerator'],
                    $app['authentication.cookie.options']
                );

                return $tracker;
            }
        );

        $app['authentication.login'] = $app->share(
            function ($app) {
                $login = new AccessControl\Login(
                    $app
                );

                return $login;
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
