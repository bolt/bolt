<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

class PathServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['pathmanager'] = $app->share(
            function ($app) {
                $filesystempath = new PlatformFileSystemPathFactory();

                return $filesystempath;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
