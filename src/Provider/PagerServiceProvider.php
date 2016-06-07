<?php

namespace Bolt\Provider;

use Bolt\Pager\PagerManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PagerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        // the provider
        $app['pager'] = $app->share(
            function () use ($app) {
                return new PagerManager();
            }
        );
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
    }
}
