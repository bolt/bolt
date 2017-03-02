<?php

namespace Bolt\Provider;

use Bolt\Configuration\PathResolverFactory;
use Bolt\Exception\BootException;
use Bolt\Helpers\Deprecated;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PathServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // @deprecated
        if (!isset($app['path_resolver_factory'])) {
            $app['path_resolver_factory'] = $app->share(
                function ($app) {
                    return (new PathResolverFactory())
                        ->setRootPath($app['path_resolver.root'])
                        ->addPaths($app['path_resolver.paths'])
                    ;
                }
            );
        }

        $app['path_resolver'] = $app->share(
            function ($app) {
                $resolver = $app['path_resolver_factory']
                    ->addPaths($app['path_resolver.paths'])
                    ->create()
                ;

                // Bolt's project directory. Not configurable.
                $resolver->define('bolt', __DIR__ . '/../../');

                return $resolver;
            }
        );
        $app['path_resolver.root'] = '';
        $app['path_resolver.paths'] = [];

        $app['pathmanager'] = $app->share(
            function () {
                Deprecated::service('pathmanager', 3.3, 'filesystem');

                $filesystempath = new PlatformFileSystemPathFactory();

                return $filesystempath;
            }
        );
    }

    public function boot(Application $app)
    {
        $resolver = $app['path_resolver'];

        $theme = $app['config']->get('general/theme');
        $resolver->define('theme', "%themes%/$theme");

        if (!file_exists($resolver->resolve('web')) &&
            (!file_exists($resolver->resolve('.bolt.yml')) || !file_exists($resolver->resolve('.bolt.php')))
        ) {
            BootException::earlyExceptionMissingLoaderConfig();
        }
    }
}
