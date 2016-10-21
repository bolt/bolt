<?php

namespace Bolt\Provider;

use Bolt\AccessControl;
use PasswordLib\Password\Factory as PasswordFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class AccessControlServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['access_control.cookie.options'] = $app->share(
            function () use ($app) {
                return [
                    'remoteaddr'   => $app['config']->get('general/cookies_use_remoteaddr', true),
                    'browseragent' => $app['config']->get('general/cookies_use_browseragent', false),
                    'httphost'     => $app['config']->get('general/cookies_use_httphost', true),
                    'lifetime'     => $app['config']->get('general/cookies_lifetime', 1209600),
                ];
            }
        );

        $app['access_control.hash.strength'] = $app->share(
            function () use ($app) {
                return max($app['config']->get('general/hash_strength'), 8);
            }
        );

        $app['access_control'] = $app->share(
            function ($app) {
                $tracker = new AccessControl\AccessChecker(
                    $app['storage.lazy'],
                    $app['request_stack'],
                    $app['session'],
                    $app['dispatcher'],
                    $app['logger.flash'],
                    $app['logger.system'],
                    $app['permissions'],
                    $app['randomgenerator'],
                    $app['access_control.cookie.options']
                );

                return $tracker;
            }
        );

        $app['access_control.login'] = $app->share(
            function ($app) {
                $login = new AccessControl\Login(
                    $app
                );

                return $login;
            }
        );

        $app['access_control.password'] = $app->share(
            function ($app) {
                $password = new AccessControl\Password($app);

                return $password;
            }
        );

        $app['password_factory'] = $app->share(
            function () {
                return new PasswordFactory();
            }
        );

        $app['token.authentication.name'] = $app->share(
            function ($app) {
                $request = $app['request_stack']->getCurrentRequest() ?: Request::createFromGlobals();
                $name = 'bolt_authtoken_' . md5($request->getHttpHost() . $request->getBaseUrl());

                return $name;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
