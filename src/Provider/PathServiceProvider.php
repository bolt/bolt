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

                return $resources;
            });
        }

        $resourcesSetup = function (ResourceManager $resources) use ($app) {
            $resources->setApp($app);

            $app['resources.check_files']($resources);
        };

        // Run resources setup either immediately if instance is given or lazily if closure is given.
        $resources = $app->raw('resources');
        if ($resources instanceof ResourceManager) {
            $resourcesSetup($resources);
        } else {
            $app['resources'] = $app->share(
                $app->extend(
                    'resources',
                    function ($resources) use ($resourcesSetup) {
                        $resourcesSetup($resources);

                        return $resources;
                    }
                )
            );
        }

        $app['classloader'] = $app->share(function ($app) {
            return $app['resources']->getClassLoader();
        });
    }

    public function boot(Application $app)
    {
    }
}
