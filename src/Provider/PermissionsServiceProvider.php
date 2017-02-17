<?php

namespace Bolt\Provider;

use Bolt\AccessControl\Permissions;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class PermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['permissions'] = function ($app) {
            $permissions = new Permissions($app);

            return $permissions;
        };
    }
}
