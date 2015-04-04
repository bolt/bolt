<?php

namespace Bolt\Provider;

use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Client as ServiceClient;
use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GuzzleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['guzzle.base_url'] = '/';

        if (!isset($app['guzzle.plugins'])) {
            $app['guzzle.plugins'] = array();
        }

        /** @deprecated */
        if ($app['deprecated.php']) {
            return $this->compat($app);
        }

        // Register a simple Guzzle Client object (requires absolute URLs when guzzle.base_url is unset)
        $app['guzzle.client'] = $app->share(
            function () use ($app) {
                $options = array('base_url' => $app['guzzle.base_url']);
                $client = new Client($options);
                foreach ($app['guzzle.plugins'] as $plugin) {
                    $client->addSubscriber($plugin);
                }

                return $client;
            }
        );
    }

    /**
     * PHP 5.3 compatibility services
     *
     * @deprecated
     *
     * @param Application $app
     */
    private function compat(Application $app)
    {
        // Register a Guzzle ServiceBuilder
        $app['guzzle'] = $app->share(
            function () use ($app) {
                if (!isset($app['guzzle.services'])) {
                    $builder = new ServiceBuilder(array());
                } else {
                    $builder = ServiceBuilder::factory($app['guzzle.services']);
                }

                return $builder;
            }
        );

        // Register a simple Guzzle Client object (requires absolute URLs when guzzle.base_url is unset)
        $app['guzzle.client'] = $app->share(
            function () use ($app) {
                $client = new ServiceClient($app['guzzle.base_url']);
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
