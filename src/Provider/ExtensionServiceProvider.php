<?php

namespace Bolt\Provider;

use Bolt\Composer\Action;
use Bolt\Composer\Factory;
use Bolt\Composer\PackageManager;
use Bolt\Extensions;
use Bolt\Extensions\ExtensionsInfoService;
use Bolt\Extensions\StatService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExtensionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['extensions'] = $app->share(
            function ($app) {
                $extensions = new Extensions($app);

                return $extensions;
            }
        );

        $app['extensions.stats'] = $app->share(
            function ($app) {
                $stats = new StatService($app);

                return $stats;
            }
        );

        $app['extend.site'] = $app['config']->get('general/extensions/site', 'https://extensions.bolt.cm/');
        $app['extend.repo'] = $app['extend.site'] . 'list.json';
        $app['extend.urls'] = [
            'list' => 'list.json',
            'info' => 'info.json'
        ];

        $extensionsPath = $app['resources']->getPath('extensions');
        $app['extend.writeable'] = is_dir($extensionsPath) && is_writable($extensionsPath) ? true : false;
        $app['extend.online'] = false;
        $app['extend.enabled'] = $app['config']->get('general/extensions/enabled', true);

        $app['extend.manager'] = $app->share(
            function ($app) {
                return new PackageManager($app);
            }
        );
        $app['extend.factory'] = $app->share(
            function ($app) {
                return new Factory($app['extend.manager']->getOptions(), $app['logger.system']);
            }
        );

        $app['extend.info'] = $app->share(
            function ($app) {
                return new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls']);
            }
        );

        // Actions
        $app['extend.action'] = $app->share(function (Application $app) {
            return new \Pimple([
                // @codingStandardsIgnoreStart
                'autoload' => $app->share(function () use ($app) { return new Action\DumpAutoload($app); }),
                'check'    => $app->share(function () use ($app) { return new Action\CheckPackage($app); }),
                'install'  => $app->share(function () use ($app) { return new Action\InstallPackage($app); }),
                'json'     => $app->share(function () use ($app) { return new Action\BoltExtendJson($app['extend.manager']->getOptions()); }),
                'remove'   => $app->share(function () use ($app) { return new Action\RemovePackage($app); }),
                'require'  => $app->share(function () use ($app) { return new Action\RequirePackage($app); }),
                'search'   => $app->share(function () use ($app) { return new Action\SearchPackage($app); }),
                'show'     => $app->share(function () use ($app) { return new Action\ShowPackage($app); }),
                'update'   => $app->share(function () use ($app) { return new Action\UpdatePackage($app); }),
                // @codingStandardsIgnoreEnd
            ]);
        });
    }

    public function boot(Application $app)
    {
    }
}
