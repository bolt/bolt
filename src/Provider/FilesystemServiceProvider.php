<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Filesystem\Matcher;
use Bolt\Filesystem\Plugin;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(
            function ($app) {
                $manager = new Manager(
                    [
                        // Bolt's project directory. Not configurable.
                        // Use for anything that's supposed to be in core:
                        // src files, our twig templates, our js & css files, our translations, etc.
                        'bolt'       => new Filesystem(new Local(__DIR__ . '/../../')),

                        // User's root directory
                        'root'       => new Filesystem(new Local($app['resources']->getPath('root'))),
                        // User's web root
                        'web'        => new Filesystem(new Local($app['resources']->getPath('web'))),
                        // User's files directory
                        'files'      => new Filesystem(new Local($app['resources']->getPath('files'))),
                        // User's config directory
                        'config'     => new Filesystem(new Local($app['resources']->getPath('config'))),
                        // User's themes directory
                        'themes'     => new Filesystem(new Local($app['resources']->getPath('themebase'))),
                        // User's currently selected theme directory
                        'theme'      => new Filesystem(new Local($app['resources']->getPath('themebase') . '/' . $app['config']->get('general/theme'))),
                        // User's extension directory
                        'extensions' => new Filesystem(new Local($app['resources']->getPath('extensions'))),
                        // User's cache directory
                        'cache'      => new Filesystem(new Local($app['resources']->getPath('cache'))),

                        // Deprecated. Use specific filesystem instead.
                        'app'        => new Filesystem(new Local($app['resources']->getPath('app'))),
                        // Deprecated. Use bolt://app/view instead.
                        'view'       => new Filesystem(new Local($app['resources']->getPath('view'))),
                        // Deprecated. Use specific filesystem instead.
                        'default'    => new Filesystem(new Local($app['resources']->getPath('files'))),
                    ],
                    [
                        new Plugin\HasUrl(),
                        new Plugin\Parents(),
                        new Plugin\ToJs(),
                        new Plugin\Authorized($app['filepermissions']),
                        new Plugin\ThumbnailUrl($app['url_generator.lazy']),
                    ]
                );

                return $manager;
            }
        );

        $app['filesystem.plugin.url'] = function () use ($app) {
            return new Plugin\AssetUrl($app['asset.packages']);
        };

        $app['filesystem.matcher'] = $app->share(function ($app) {
            return new Matcher($app['filesystem'], $app['filesystem.matcher.mount_points']);
        });

        $app['filesystem.matcher.mount_points'] = ['files', 'themes', 'theme'];
    }

    public function boot(Application $app)
    {
        // Add url plugin here to prevent circular dependency.
        $app['filesystem']->addPlugin($app['filesystem.plugin.url']);
    }
}
