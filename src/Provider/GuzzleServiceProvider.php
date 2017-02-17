<?php

namespace Bolt\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class GuzzleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['guzzle.base_url'] = '/';

        $app['guzzle.api_version'] = function () {
            return version_compare(Client::VERSION, '6.0.0', '>=') ? 6 : 5;
        };

        if (!isset($app['guzzle.handler_stack'])) {
            $app['guzzle.handler_stack'] = function () {
                return HandlerStack::create();
            };
        }

        // Register a simple Guzzle Client object (requires absolute URLs when guzzle.base_url is unset)
        $app['guzzle.client'] = function () use ($app) {
            $options = [
                'base_uri' => $app['guzzle.base_url'],
                'handler'  => $app['guzzle.handler_stack'],
            ];
            $client = new Client($options);

            return $client;
        };
    }
}
