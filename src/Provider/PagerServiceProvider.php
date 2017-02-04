<?php

namespace Bolt\Provider;

use Bolt\Pager\PagerManager;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class PagerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        // the provider
        $app['pager'] = 
            function () {
                return new PagerManager();
            }
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
