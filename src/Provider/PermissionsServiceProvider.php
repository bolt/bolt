<?php

namespace Bolt\Provider;

use Bolt\AccessControl\Permissions;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class PermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['permissions'] = 
            function ($app) {
                $permissions = new Permissions($app);

                return $permissions;
            }
        ;
    }
}
