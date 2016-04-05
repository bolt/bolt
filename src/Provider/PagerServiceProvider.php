<?php

namespace Bolt\Provider;

use Bolt\Pager\PagerManager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

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
                $manager = $app['pager.manager_factory']();

                return $manager;
            }
        );

        $app['pager.manager_factory'] = $app->protect(
            $app->share(
                function (Request $request = null) use ($app) {
                    $manager = new PagerManager();
                    if (!$request) {
                        $request = $app['request_stack']->getCurrentRequest();
                    }
                    if ($request) {
                        $manager->initialize($request);
                    }

                    return $manager;
                }
            )
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
