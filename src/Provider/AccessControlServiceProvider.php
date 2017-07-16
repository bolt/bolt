<?php

namespace Bolt\Provider;

use Bolt\AccessControl;
use Bolt\AccessControl\PasswordHashManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class AccessControlServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['access_control.cookie.options'] = function () use ($app) {
            return [
                'remoteaddr'   => $app['config']->get('general/cookies_use_remoteaddr', true),
                'browseragent' => $app['config']->get('general/cookies_use_browseragent', false),
                'httphost'     => $app['config']->get('general/cookies_use_httphost', true),
                'lifetime'     => $app['config']->get('general/cookies_lifetime', 1209600),
            ];
        };

        $app['access_control.hash.strength'] = function () use ($app) {
            return max($app['config']->get('general/hash_strength'), 8);
        };

        $app['access_control'] = function ($app) {
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
        };

        $app['access_control.login'] = function ($app) {
            $login = new AccessControl\Login($app);

            return $login;
        };

        $app['access_control.password'] = function ($app) {
            $password = new AccessControl\Password($app);

            return $password;
        };

        /** @deprecated Deprecated since 4.0-dev to be removed BEFORE 4.0.0 is released */
        $app['password_hash.algorithm'] = PASSWORD_DEFAULT;
        /** @deprecated Deprecated since 4.0-dev to be removed BEFORE 4.0.0 is released */
        $app['password_hash.options'] = function () {
            return [
                'cost' => 8,
            ];
        };
        /** @deprecated Deprecated since 4.0-dev to be removed BEFORE 4.0.0 is released */
        $app['password_hash.manager'] = function ($app) {
            return new PasswordHashManager($app['password_hash.algorithm'], $app['password_hash.options']);
        };

        $app['token.authentication.name'] = function ($app) {
            $request = $app['request_stack']->getCurrentRequest() ?: Request::createFromGlobals();
            $name = 'bolt_authtoken_' . md5($request->getHttpHost() . $request->getBaseUrl());

            return $name;
        };

        $app['token.authentication.name.lazy'] = $app->protect(
            function () use ($app) {
                return $app['token.authentication.name'];
            }
        );
    }
}
