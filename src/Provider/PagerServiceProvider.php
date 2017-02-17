<?php

namespace Bolt\Provider;

use Bolt\Pager\PagerManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class PagerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        // the provider
        $app['pager'] = function () {
            return new PagerManager();
        };
    }
}
