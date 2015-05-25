<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Token/cookie name service provider.
 *
 * NOTE: We use hostname as that will include the TCP/IP port used if
 * non-standard, and the root URL as multiple installations can exist on the
 * same host using subdirectories.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TokenServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['token.session.name'] = $app->share(
            function ($app) {
                $name = 'bolt_session_' . md5($app['resources']->getRequest('hostname') . $app['resources']->getUrl('root'));

                return $name;
            }
        );

        $app['token.authentication.name'] = $app->share(
            function ($app) {
                $name = 'bolt_authtoken_' . md5($app['resources']->getRequest('hostname') . $app['resources']->getUrl('root'));

                return $name;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
