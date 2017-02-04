<?php

namespace Bolt\Provider;

use Bolt\Stack;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class StackServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['stack'] = 
            function ($app) {
                $stack = new Stack(
                    $app['filesystem.matcher'],
                    $app['users'],
                    $app['session'],
                    $app['config']->get('general/accept_file_types')
                );

                return $stack;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
