<?php

namespace Bolt\Provider;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class PermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['permissions'] = $app->share(function ($app) {

            $permissions = new Bolt\Permissions($app);

            return $permissions;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
