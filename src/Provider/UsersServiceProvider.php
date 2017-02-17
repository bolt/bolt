<?php

namespace Bolt\Provider;

use Bolt\Users;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class UsersServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['users'] = function ($app) {
            $users = new Users($app);

            return $users;
        };
    }
}
