<?php

namespace Bolt\Provider;

use Bolt\Pager\PagerManager;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class PagerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        // the provider
        $app['pager'] = 
            function () {
                return new PagerManager();
            }
        ;
    }
}
