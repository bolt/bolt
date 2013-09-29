<?php

namespace Bolt\Provider;

use Bolt;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class StackServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {

        $app['stack'] = $app->share(function () {

            $stack = new Bolt\Stack();

            return $stack;

        });

    }

    public function boot(SilexApplication $app)
    {
    }
}
