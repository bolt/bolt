<?php

namespace Bolt\Provider;

use Bolt\Routing\ControllerCollection;
use Bolt\Routing\ControllerResolver;
use Bolt\Routing\UrlMatcher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controllers_factory'] = function ($app) {
            return new ControllerCollection($app['route_factory']);
        };

        $app['url_matcher'] = $app->share(
            function ($app) {
                return new UrlMatcher($app['routes'], $app['request_context']);
            }
        );

        $app['resolver'] = $app->share(
            function ($app) {
                return new ControllerResolver($app, $app['logger']);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
