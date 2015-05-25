<?php

namespace Bolt\Provider;

use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GuzzleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['guzzle.base_url'] = '/';

        if (!isset($app['guzzle.plugins'])) {
            $app['guzzle.plugins'] = [];
        }

        // Register a simple Guzzle Client object (requires absolute URLs when guzzle.base_url is unset)
        $app['guzzle.client'] = $app->share(
            function () use ($app) {
                $options = ['base_url' => $app['guzzle.base_url']];
                $client = new Client($options);
                foreach ($app['guzzle.plugins'] as $plugin) {
                    $client->addSubscriber($plugin);
                }

                return $client;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
