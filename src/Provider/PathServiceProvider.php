<?php

namespace Bolt\Provider;

use Bolt\Configuration\Composer;
use Bolt\Configuration\LazyPathsProxy;
use Bolt\Configuration\PathResolverFactory;
use Bolt\Configuration\PreBoot\ConfigurationFile;
use Bolt\Configuration\ResourceManager;
use Bolt\Exception\BootException;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class PathServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // @deprecated
        if (!isset($app['path_resolver_factory'])) {
            $app['path_resolver_factory'] = 
                function ($app) {
                    return (new PathResolverFactory())
                        ->setRootPath($app['path_resolver.root'])
                        ->addPaths($app['path_resolver.paths'])
                    ;
                }
            ;
        }

        $app['path_resolver'] = 
            function ($app) {
                $resolver = $app['path_resolver_factory']
                    ->addPaths($app['path_resolver.paths'])
                    ->create()
                ;

                // Bolt's project directory. Not configurable.
                $resolver->define('bolt', __DIR__ . '/../../');

                return $resolver;
            }
        ;
        $app['path_resolver.root'] = '';
        $app['path_resolver.paths'] = [];

        $app['pathmanager'] = 
            function () {
                $filesystempath = new PlatformFileSystemPathFactory();

                return $filesystempath;
            }
        ;

        $app['resources.check_files'] = $app->protect(
            function (ResourceManager $resources) {
                static $initialized;
                if ($initialized) {
                    return;
                }
                $initialized = true;

                if (!file_exists($resources->getPath('web')) && $resources instanceof Composer) {
                    BootException::earlyExceptionMissingLoaderConfig();
                }

                ConfigurationFile::checkConfigFiles(
                    ['config', 'contenttypes', 'menu', 'permissions', 'routing', 'taxonomy'],
                    $resources->getPath('src/../app/config'),
                    $resources->getPath('config')
                );
            }
        );

        if (!isset($app['resources'])) {
            $app['resources'] = 
                function ($app) {
                    $resources = new ResourceManager(new \ArrayObject([
                        'rootpath'              => $app['path_resolver.root'],
                        'path_resolver'         => $app['path_resolver'],
                        'path_resolver_factory' => $app['path_resolver_factory'],
                        'pathmanager'           => $app['pathmanager'],
                    ]));

                    return $resources;
                }
            ;
        }

        $resourcesSetup = function (ResourceManager $resources) use ($app) {
            // This is to sync service if ResourceManager is created without the factory passed in.
            // In most cases it is so this technically doesn't change anything.
            $app['path_resolver_factory'] = $resources->getPathResolverFactory();

            $resources->setApp($app);

            $app['resources.check_files']($resources);
        };

        // Run resources setup either immediately if instance is given or lazily if closure is given.
        $resources = $app->raw('resources');
        if ($resources instanceof ResourceManager) {
            $resourcesSetup($resources);
        } else {
            $app['resources'] = 
                $app->extend(
                    'resources',
                    function ($resources) use ($resourcesSetup) {
                        $resourcesSetup($resources);

                        return $resources;
                    }
                )
            ;
        }

        $app['classloader'] = function ($app) {
            return $app['resources']->getClassLoader();
        };

        $app['paths'] = function ($app) {
            return new LazyPathsProxy(function () use ($app) {
                return $app['resources'];
            });
        };
    }

    public function boot(Application $app)
    {
        $theme = $app['config']->get('general/theme');
        $app['path_resolver']->define('theme', "%themes%/$theme");
    }
}
