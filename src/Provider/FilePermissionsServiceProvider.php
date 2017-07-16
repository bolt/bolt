<?php

namespace Bolt\Provider;

use Bolt\Filesystem\FilePermissions;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * @author Benjamin Georgeault <benjamin@wedgesama.fr>
 */
class FilePermissionsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['filepermissions'] = function ($app) {
            $filePermissions = new FilePermissions($app['config']);

            return $filePermissions;
        };
    }
}
