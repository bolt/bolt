<?php

namespace Bolt\Provider;

use Bolt\Routing\Canonical;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

/**
 * Canonical service provider.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CanonicalServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['canonical'] = function ($app) {
            return new Canonical(
                $app['url_generator.lazy'],
                $app['config']->get('general/force_ssl'),
                $app['config']->get('general/canonical')
            );
        };
    }

    public function boot(Application $app)
    {
        if (isset($app['canonical'])) {
            $app['dispatcher']->addSubscriber($app['canonical']);
        }
    }
}
