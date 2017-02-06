<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Filesystem\Matcher;
use Bolt\Filesystem\Plugin;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Silex\Api\BootableProviderInterface;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        // These can be called early
        $app['filesystem.config'] = function ($app) {
            $fs = new Filesystem(new Local($app['path_resolver']->resolve('config')));
            $fs->setMountPoint('config');

            return $fs;
        };

        $app['filesystem.cache'] = function ($app) {
            $fs = new Filesystem(new Local($app['path_resolver']->resolve('cache')));
            $fs->setMountPoint('cache');

            return $fs;
        };

        $app['filesystem.themes'] = function ($app) {
            $fs = new Filesystem(new Local($app['path_resolver']->resolve('themes')));
            $fs->setMountPoint('themes');

            return $fs;
        };

        // Calling this before boot … all bets are off … and if Bolt breaks, you get to keep both pieces!
        // @TODO :fire: this when the new configuration loading lands
        $app['filesystem.theme'] = function ($app) {
            $fs = new Filesystem(new Local($app['path_resolver']->resolve('%themes%/' . $app['config']->get('general/theme'))));
            $fs->setMountPoint('theme');

            return $fs;
        };

        // Don't call this until boot.
        $app['filesystem'] = 
            function ($app) {
                $manager = new Manager(
                    [
                        // Bolt's project directory. Not configurable.
                        // Use for anything that's supposed to be in core:
                        // src files, our twig templates, our js & css files, our translations, etc.
                        'bolt'              => new Filesystem(new Local($app['path_resolver']->resolve('bolt'))),
                        // Root directory. Not configurable.
                        'root'              => new Filesystem(new Local($app['path_resolver']->resolve('root'))),

                        // User's web root
                        'web'               => new Filesystem(new Local($app['path_resolver']->resolve('web'))),
                        // User's files directory
                        'files'             => new Filesystem(new Local($app['path_resolver']->resolve('files'))),
                        // User's synced bolt assets directory
                        'bolt_assets'       => new Filesystem(new Local($app['path_resolver']->resolve('bolt_assets'))),
                        // User's config directory
                        'config'            => $app['filesystem.config'],
                        // User's themes directory
                        'themes'            => $app['filesystem.themes'],
                        // User's currently selected theme directory
                        'theme'             => $app['filesystem.theme'],
                        // User's extension directory
                        'extensions'        => new Filesystem(new Local($app['path_resolver']->resolve('extensions'))),
                        // User's extension config directory
                        'extensions_config' => new Filesystem(new Local($app['path_resolver']->resolve('extensions_config'))),
                        // User's cache directory
                        'cache'             => $app['filesystem.cache'],

                        // Deprecated. Use specific filesystem instead.
                        'app'        => new Filesystem(new Local($app['resources']->getPath('app'))),
                        // Deprecated. Use bolt_assets filesystem instead.
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
        ;

        $app['filesystem.plugin.url'] = function () use ($app) {
            return new Plugin\AssetUrl($app['asset.packages']);
        };

        $app['filesystem.matcher'] = function ($app) {
            return new Matcher($app['filesystem'], $app['filesystem.matcher.mount_points']);
        };

        $app['filesystem.matcher.mount_points'] = ['files', 'themes', 'theme'];
    }

    public function boot(Application $app)
    {
        // Add url plugin here to prevent circular dependency.
        $app['filesystem']->addPlugin($app['filesystem.plugin.url']);
        // "bolt" filesystem cannot use the "bolt" asset package.
        $app['filesystem']->getFilesystem('bolt')->addPlugin(new Plugin\NoAssetUrl());
    }
}
