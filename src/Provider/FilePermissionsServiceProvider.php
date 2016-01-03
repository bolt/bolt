<?php

namespace Bolt\Provider;

use Bolt\Filesystem\FilePermissions;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * @author Benjamin Georgeault <benjamin@wedgesama.fr>
 */
class FilePermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filepermissions'] = $app->share(
            function ($app) {
                $filePermissions = new FilePermissions($app['config']);

                return $filePermissions;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
