<?php

namespace Bolt\Provider;

use Bolt\Configuration\PathResolver;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

class PathServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['path_resolver'] = function ($app) {
            $resolver = new PathResolver($app['path_resolver.root'], PathResolver::defaultPaths());

            foreach ($app['path_resolver.paths'] as $name => $path) {
                $resolver->define($name, $path);
            }

            // Bolt's project directory. Not configurable.
            $resolver->define('bolt', __DIR__ . '/../../');

            return $resolver;
        };
        $app['path_resolver.root'] = '';
        $app['path_resolver.paths'] = [];
    }

    public function boot(Application $app)
    {
        $resolver = $app['path_resolver'];

        $theme = $app['config']->get('general/theme');
        $resolver->define('theme', "%themes%/$theme");
    }
}
