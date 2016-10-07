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
        // These can be called early
        $app['filesystem.config'] = $app->share(function ($app) {
            $fs = new Filesystem(new Local($app['resources']->getPath('config')));
            $fs->setMountPoint('config');

            return $fs;
        });

        $app['filesystem.cache'] = $app->share(function ($app) {
            $fs = new Filesystem(new Local($app['resources']->getPath('cache')));
            $fs->setMountPoint('cache');

            return $fs;
        });

        $app['filesystem.themes'] = $app->share(function ($app) {
            $fs = new Filesystem(new Local($app['resources']->getPath('themebase')));
            $fs->setMountPoint('themes');

            return $fs;
        });

        // Calling this before boot … all bets are off … and if Bolt breaks, you get to keep both pieces!
        // @TODO :fire: this when the new configuration loading lands
        $app['filesystem.theme'] = $app->share(function ($app) {
            $fs = new Filesystem(new Local($app['resources']->getPath('themebase') . '/' . $app['config']->get('general/theme')));
            $fs->setMountPoint('theme');

            return $fs;
        });

        // Don't call this until boot.
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
                        'themes'     => $app['filesystem.themes'],
                        'theme'      => $app['filesystem.theme'],
                        'extensions' => new Filesystem(new Local($app['resources']->getPath('extensions'))),
                        'config'     => $app['filesystem.config'],
                        'cache'      => $app['filesystem.cache'],
                    ],
                    [
                        new Plugin\PublicUrl($app),
                        new Plugin\Authorized($app),
                        new Plugin\ThumbnailUrl($app),
                    ]
                );

                return $manager;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
