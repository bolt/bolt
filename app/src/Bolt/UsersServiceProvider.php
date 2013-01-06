<?php

namespace Bolt;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class UsersServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['users'] = $app->share(function ($app) {

            $users = new Bolt\Users($app);

            return $users;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
