<?php

namespace Bolt\Provider;

use Bolt\Routing\Canonical;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Silex\Api\BootableProviderInterface;

/**
 * Canonical service provider.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CanonicalServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['canonical'] = 
            function ($app) {
                return new Canonical(
                    $app['request_stack'],
                    $app['request_context'],
                    $app['url_generator'],
                    $app['config']->get('general/canonical')
                );
            }
        ;
    }

    public function boot(Application $app)
    {
        if (isset($app['canonical'])) {
            $app['dispatcher']->addSubscriber($app['canonical']);
        }
    }
}
