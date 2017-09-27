<?php

namespace Bolt\Provider;

use Bolt\Routing\Canonical;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Canonical service provider.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CanonicalServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['canonical'] = $app->share(
            function ($app) {
                return new Canonical(
                    $app['url_generator.lazy'],
                    $app['config']->get('general/force_ssl'),
                    $app['config']->get('general/canonical')
                );
            }
        );
    }

    public function boot(Application $app)
    {
        if (isset($app['canonical'])) {
            $app['dispatcher']->addSubscriber($app['canonical']);
        }
    }
}
