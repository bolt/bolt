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
                $filepermissions = new FilePermissions($app);

                return $filepermissions;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
