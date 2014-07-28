<?php

namespace Bolt\Provider;

use Bolt\Permissions;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['permissions'] = $app->share(
            function ($app) {
                $permissions = new Permissions($app);

                return $permissions;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
