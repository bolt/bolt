<?php

namespace Bolt\Provider;

use Bolt\Routing\UrlMatcher;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RoutingServiceProvider implements ServiceProviderInterface {

    public function register(Application $app)
    {
        $app['url_matcher'] = $app->share(
            function ($app) {
                return new UrlMatcher($app['routes'], $app['request_context']);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
