<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
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
                        'root'       => new Filesystem(new Local($app['resources']->getPath('root'))),
                        'web'        => new Filesystem(new Local($app['resources']->getPath('web'))),
                        'app'        => new Filesystem(new Local($app['resources']->getPath('app'))),
                        'view'       => new Filesystem(new Local($app['resources']->getPath('view'))),
                        'default'    => new Filesystem(new Local($app['resources']->getPath('files'))),
                        'files'      => new Filesystem(new Local($app['resources']->getPath('files'))),
                        'config'     => new Filesystem(new Local($app['resources']->getPath('config'))),
                        'themes'     => new Filesystem(new Local($app['resources']->getPath('themebase'))),
                        'theme'      => new Filesystem(new Local($app['resources']->getPath('themebase') . '/' . $app['config']->get('general/theme'))),
                        'extensions' => new Filesystem(new Local($app['resources']->getPath('extensions'))),
                        'cache'      => new Filesystem(new Local($app['resources']->getPath('cache'))),
                    ],
                    [
                        new Plugin\HasUrl($app),
                        new Plugin\Parents($app),
                        new Plugin\ToJson($app),
                        new Plugin\Authorized($app),
                        new Plugin\ThumbnailUrl($app),
                    ]
                );

                return $manager;
            }
        );

        $app['filesystem.plugin.url'] = function () use ($app) {
            return new Plugin\AssetUrl($app['asset.packages']);
        };
    }

    public function boot(Application $app)
    {
        // Add url plugin here to prevent circular dependency.
        $app['filesystem']->addPlugin($app['filesystem.plugin.url']);
    }
}
