<?php

namespace Bolt\Provider;

use Bolt\Composer\Action;
use Bolt\Composer\EventListener\BufferIOListener;
use Bolt\Composer\JsonManager;
use Bolt\Composer\PackageManager;
use Bolt\Composer\Satis;
use Bolt\Extension\Manager;
use Bolt\Filesystem\Handler\JsonFile;
use Composer\IO\BufferIO;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['extensions'] = $app->share(
            function ($app) {
                $loader = new Manager(
                    $app['filesystem']->getFilesystem('extensions'),
                    $app['filesystem']->getFilesystem('web'),
                    $app['logger.flash'],
                    $app['config']
                );

                return $loader;
            }
        );

        $app['extensions.stats'] = $app->share(
            function ($app) {
                $stats = new Satis\StatService($app['guzzle.client'], $app['logger.system'], $app['extend.site']);

                return $stats;
            }
        );

        $app['extend.site'] = $app['config']->get('general/extensions/site', 'https://extensions.bolt.cm/');
        $app['extend.repo'] = $app['extend.site'] . 'list.json';
        $app['extend.urls'] = [
            'list' => 'list.json',
            'info' => 'info.json',
        ];

        $app['extend.online'] = false;
        $app['extend.enabled'] = $app['config']->get('general/extensions/enabled', true);
        $app['extend.writeable'] = $app->share(
            function () use ($app) {
                $extensionsPath = $app['resources']->getPath('extensions');

                return is_dir($extensionsPath) && is_writable($extensionsPath) ? true : false;
            }
        );

        $app['extend.manager'] = $app->share(
            function ($app) {
                return new PackageManager($app);
            }
        );

        $app['extend.manager.json'] = $app->share(
            function ($app) {
                return new JsonManager($app);
            }
        );

        $app['extend.listener'] = $app->share(
            function ($app) {
                return new BufferIOListener($app['extend.manager'], $app['logger.system']);
            }
        );

        $app['extend.info'] = $app->share(
            function ($app) {
                return new Satis\QueryService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);
            }
        );

        // Actions
        $app['extend.action'] = $app->share(
            function (Application $app) {
                return new \Pimple(
                    [
                        // @codingStandardsIgnoreStart
                        'autoload' => $app->share(function () use ($app) { return new Action\DumpAutoload($app); }),
                        'check'    => $app->share(function () use ($app) { return new Action\CheckPackage($app); }),
                        'install'  => $app->share(function () use ($app) { return new Action\InstallPackage($app); }),
                        'remove'   => $app->share(function () use ($app) { return new Action\RemovePackage($app); }),
                        'require'  => $app->share(function () use ($app) { return new Action\RequirePackage($app); }),
                        'search'   => $app->share(function () use ($app) { return new Action\SearchPackage($app); }),
                        'show'     => $app->share(function () use ($app) { return new Action\ShowPackage($app); }),
                        'update'   => $app->share(function () use ($app) { return new Action\UpdatePackage($app); }),
                        // @codingStandardsIgnoreEnd
                    ]
                );
            }
        );

        $app['extend.action.io'] = $app->share(
            function () {
                return new BufferIO();
            }
        );

        $app['extend.action.options'] = $app->share(
            function ($app) {
                return new Action\Options($app['filesystem']->get('extensions://composer.json', new JsonFile()));
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
