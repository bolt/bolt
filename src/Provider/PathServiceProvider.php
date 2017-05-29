<?php

namespace Bolt\Provider;

use Bolt\Configuration\PathResolver;
use Bolt\Exception\BootException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PathServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['path_resolver'] = $app->share(
            function ($app) {
                $resolver = new PathResolver($app['path_resolver.root'], PathResolver::defaultPaths());

                foreach ($app['path_resolver.paths'] as $name => $path) {
                    $resolver->define($name, $path);
                }

                // Bolt's project directory. Not configurable.
                $resolver->define('bolt', __DIR__ . '/../../');

                return $resolver;
            }
        );
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
