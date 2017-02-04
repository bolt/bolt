<?php

namespace Bolt\Provider;

use Bolt\Filesystem\FilePermissions;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

/**
 * @author Benjamin Georgeault <benjamin@wedgesama.fr>
 */
class FilePermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['filepermissions'] = 
            function ($app) {
                $filePermissions = new FilePermissions($app['config']);

                return $filePermissions;
            }
        ;
    }
}
