<?php

namespace Bolt\Provider;

use Bolt\Users;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UsersServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        $app['users'] = $app->share(function ($app) {

            $users = new Users($app);

            return $users;

        });

    }

    public function boot(Application $app)
    {
    }
}
