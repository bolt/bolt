<?php

namespace Bolt\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GuzzleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['guzzle.base_url'] = '/';

        $app['guzzle.api_version'] = $app->share(
            function () {
                return version_compare(Client::VERSION, '6.0.0', '>=') ? 6 : 5;
            }
        );

        if (!isset($app['guzzle.handler_stack'])) {
            $app['guzzle.handler_stack'] = $app->share(
                function () {
                    return HandlerStack::create();
                }
            );
        }

        // Register a simple Guzzle Client object (requires absolute URLs when guzzle.base_url is unset)
        $app['guzzle.client'] = $app->share(
            function () use ($app) {
                $options = [
                    'base_uri' => $app['guzzle.base_url'],
                    'handler'  => $app['guzzle.handler_stack'],
                ];
                $client = new Client($options);

                return $client;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
