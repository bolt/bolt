<?php

namespace Bolt\Provider;

use Bolt\AccessControl\Permissions;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class PermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['permissions'] = 
            function ($app) {
                $permissions = new Permissions($app);

                return $permissions;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
