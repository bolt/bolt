<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Manager;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * @author Benjamin Georgeault <benjamin@wedgesama.fr>
 */
class FilesystemProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(
            function ($app) {
                $manager = new Manager($app);

                return $manager;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
