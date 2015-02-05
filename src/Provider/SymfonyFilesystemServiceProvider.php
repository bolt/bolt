<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provider for Symfony Filesystem
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SymfonyFilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['symfony.filesystem'] = $app->share(
            function(Application $app) {
                return new Filesystem();
        });
    }

    public function boot(Application $app)
    {
    }
}
