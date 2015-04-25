<?php

namespace Bolt\Provider;

use Bolt\Routing\ControllerCollection;
use Bolt\Routing\ControllerResolver;
use Bolt\Routing\RedirectListener;
use Bolt\Routing\UrlMatcher;
use Silex\Application;
use Silex\Route;
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

        $app['route_factory'] = $app->extend('route_factory', function (Route $route, $app) {
            if ($app['config']->get('general/enforce_ssl')) {
                $route->requireHttps();
            }
            return $route;
        });
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber(new RedirectListener($app['session'], $app['url_generator'], $app['users']));
    }
}
