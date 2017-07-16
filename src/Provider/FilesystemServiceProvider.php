<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Filesystem\Matcher;
use Bolt\Filesystem\Plugin;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['filesystem'] = function ($app) {
            $manager = new Manager(
                [
                    // Bolt's project directory. Not configurable.
                    // Use for anything that's supposed to be in core:
                    // src files, our twig templates, our js & css files, our translations, etc.
                    'bolt'              => new Filesystem(new Local($app['path_resolver']->resolve('bolt'), LOCK_EX, Local::SKIP_LINKS)),
                    // Root directory. Not configurable.
                    'root'              => new Filesystem(new Local($app['path_resolver']->resolve('root'), LOCK_EX, Local::SKIP_LINKS)),

                    // User's web root
                    'web'               => new Filesystem(new Local($app['path_resolver']->resolve('web'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's files directory
                    'files'             => new Filesystem(new Local($app['path_resolver']->resolve('files'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's synced bolt assets directory
                    'bolt_assets'       => new Filesystem(new Local($app['path_resolver']->resolve('bolt_assets'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's config directory
                    'config'            => new Filesystem(new Local($app['path_resolver']->resolve('config'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's themes directory
                    'themes'            => new Filesystem(new Local($app['path_resolver']->resolve('themes'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's extension directory
                    'extensions'        => new Filesystem(new Local($app['path_resolver']->resolve('extensions'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's extension config directory
                    'extensions_config' => new Filesystem(new Local($app['path_resolver']->resolve('extensions_config'), LOCK_EX, Local::SKIP_LINKS)),
                    // User's cache directory
                    'cache'             => new Filesystem(new Local($app['path_resolver']->resolve('cache'), LOCK_EX, Local::SKIP_LINKS)),
                    // Volatile data
                    'var'               => new Filesystem(new Local($app['path_resolver']->resolve('var'), LOCK_EX, Local::SKIP_LINKS)),
                ],
                [
                    new Plugin\HasUrl(),
                    new Plugin\Parents(),
                    new Plugin\ToJs(),
                    new Plugin\ThumbnailUrl($app['url_generator.lazy']),
                ]
            );

            return $manager;
        };

        // Separated to prevent circular dependency.
        // config depends on filesystem, so filesystem cannot depend on config
        $app['filesystem.theme'] = function ($app) {
            $fs = new Filesystem(new Local($app['path_resolver']->resolve('%themes%/' . $app['config']->get('general/theme'))));

            return $fs;
        };

        $app['filesystem.plugin.authorized'] = function ($app) {
            return new Plugin\Authorized($app['filepermissions']);
        };

        $app['filesystem.plugin.url'] = function ($app) {
            return new Plugin\AssetUrl($app['asset.packages']);
        };

        $app['filesystem.matcher'] = function ($app) {
            return new Matcher($app['filesystem'], $app['filesystem.matcher.mount_points']);
        };

        $app['filesystem.matcher.mount_points'] = ['files', 'themes', 'theme'];
    }

    public function boot(Application $app)
    {
        $filesystem = $app['filesystem'];

        // User's currently selected theme directory.
        // Add theme filesystem here to prevent circular dependency.
        $filesystem->mountFilesystem('theme', $app['filesystem.theme']);

        // Add authorized plugin here to prevent circular dependency.
        $filesystem->addPlugin($app['filesystem.plugin.authorized']);
        // Add url plugin here to prevent circular dependency.
        $filesystem->addPlugin($app['filesystem.plugin.url']);
        // "bolt" filesystem cannot use the "bolt" asset package.
        $filesystem->getFilesystem('bolt')->addPlugin(new Plugin\NoAssetUrl());
    }
}
