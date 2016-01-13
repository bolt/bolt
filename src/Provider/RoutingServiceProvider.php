<?php

namespace Bolt\Provider;

use Bolt\Routing\CallbackResolver;
use Bolt\Routing\ControllerCollection;
use Bolt\Routing\ControllerResolver;
use Bolt\Routing\LazyUrlGenerator;
use Bolt\Routing\RootControllerCollection;
use Bolt\Routing\UrlGeneratorFragmentWrapper;
use Bolt\Routing\UrlMatcher;
use Silex\Application;
use Silex\Route;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controllers_factory'] = function ($app) {
            return new ControllerCollection($app['route_factory']);
        };

        $app['controllers'] = $app->share(
            function ($app) {
                return new RootControllerCollection($app, $app['dispatcher'], $app['route_factory']);
            }
        );

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

        $app['callback_resolver'] = $app->share(
            function ($app) {
                return new CallbackResolver($app, $app['controller.classmap']);
            }
        );

        $app['route_factory'] = $app->extend(
            'route_factory',
            function (Route $route, $app) {
                if ($app['config']->get('general/enforce_ssl')) {
                    $route->requireHttps();
                }

                return $route;
            }
        );

        $app['url_generator'] = $app->share(
            $app->extend(
                'url_generator',
                function ($urlGenerator) {
                    return new UrlGeneratorFragmentWrapper($urlGenerator);
                }
            )
        );

        $app['url_generator.lazy'] = $app->share(
            function ($app) {
                return new LazyUrlGenerator(
                    function () use ($app) {
                        return $app['url_generator'];
                    }
                );
            }
        );
    }

    public function boot(Application $app)
    {
        if ($proxies = $app['config']->get('general/trustProxies')) {
            Request::setTrustedProxies($proxies);
        }
    }
}
