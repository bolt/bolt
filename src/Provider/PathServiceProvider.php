<?php

namespace Bolt\Provider;

use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PathServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['pathmanager'] = $app->share(
            function () {
                $filesystempath = new PlatformFileSystemPathFactory();

                return $filesystempath;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
