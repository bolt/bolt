<?php

namespace Bolt\Provider;

use Bolt\Users;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class UsersServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['users'] = 
            function ($app) {
                $users = new Users($app);

                return $users;
            }
        ;
    }
}
