<?php

namespace Bolt\Provider;

use Bolt\Users;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class UsersServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['users'] = 
            function ($app) {
                $users = new Users($app);

                return $users;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
