<?php

namespace Bolt\Provider;

use Bolt\Stack;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class StackServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['stack'] = function ($app) {
            $stack = new Stack(
                $app['filesystem.matcher'],
                $app['users'],
                $app['session'],
                $app['config']->get('general/accept_file_types')
            );

            return $stack;
        };
    }
}
