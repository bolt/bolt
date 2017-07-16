<?php

namespace Bolt\Provider;

use Bolt\Routing\CallbackResolver;
use Bolt\Routing\ControllerCollection;
use Bolt\Routing\ControllerResolver;
use Bolt\Routing\LazyUrlGenerator;
use Bolt\Routing\RootControllerCollection;
use Bolt\Routing\UrlGeneratorFragmentWrapper;
use Bolt\Routing\UrlMatcher;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Silex\Route;
use Symfony\Component\HttpFoundation\Request;

class RoutingServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['controllers_factory'] = $app->factory(function ($app) {
            return new ControllerCollection($app['route_factory']);
        });

        $app['controllers'] = function ($app) {
            return new RootControllerCollection($app, $app['dispatcher'], $app['route_factory']);
        };

        $app['url_matcher'] = function ($app) {
            return new UrlMatcher($app['routes'], $app['request_context']);
        };

        $app['resolver'] = function ($app) {
            return new ControllerResolver($app, $app['logger']);
        };

        $app['callback_resolver'] = function ($app) {
            return new CallbackResolver($app);
        };

        $app['route_factory'] = $app->extend(
            'route_factory',
            function (Route $route, $app) {
                if ($app['config']->get('general/enforce_ssl')) {
                    $route->requireHttps();
                }

                return $route;
            }
        );

        $app['url_generator'] = $app->extend(
            'url_generator',
            function ($urlGenerator) {
                return new UrlGeneratorFragmentWrapper($urlGenerator);
            }
        );

        $app['url_generator.lazy'] = function ($app) {
            return new LazyUrlGenerator(
                function () use ($app) {
                    return $app['url_generator'];
                }
            );
        };
    }

    public function boot(Application $app)
    {
        if ($proxies = $app['config']->get('general/trustProxies')) {
            Request::setTrustedProxies($proxies);
        }
    }
}
