<?php

namespace Bolt\Provider;

use Bolt\Configuration\PreBoot\ConfigurationFile;
use Bolt\Configuration\ResourceManager;
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

        $app['resources.check_files'] = $app->protect(function (ResourceManager $resources) use ($app) {
            static $initialized;
            if ($initialized) {
                return;
            }
            $initialized = true;

            ConfigurationFile::checkConfigFiles(
                ['config', 'contenttypes', 'menu', 'permissions', 'routing', 'taxonomy'],
                $resources->getPath('src/../app/config'),
                $resources->getPath('config')
            );
        });

        if (!isset($app['resources'])) {
            $app['resources'] = $app->share(function ($app) {
                $resources = new ResourceManager($app);
                $resources->setApp($app);

                $app['resources.check_files']($resources);

                return $resources;
            });
        }

        // If resources was passed into constructor set app.
        $resources = $app->raw('resources');
        if ($resources instanceof ResourceManager) {
            $resources->setApp($app);

            $app['resources.check_files']($resources);

            $app['classloader'] = $app->share(function ($app) {
                return $app['resources']->getClassLoader();
            });
        }
    }

    public function boot(Application $app)
    {
    }
}
