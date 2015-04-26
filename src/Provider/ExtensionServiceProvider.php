<?php

namespace Bolt\Provider;

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
        $app['extend.urls'] = array(
            'list' => 'list.json',
            'info' => 'info.json'
        );

        $extensionsPath = $app['resources']->getPath('extensions');
        $app['extend.writeable'] = is_dir($extensionsPath) && is_writable($extensionsPath) ? true : false;
        $app['extend.online'] = false;
        $app['extend.enabled'] = $app['config']->get('general/extensions/enabled', true);

        // This exposes the main upload object as a service
        $app['extend.manager'] = $app->share(
            function ($app) {
                return new PackageManager($app);
            }
        );

        $app['extend.info'] = $app->share(
            function ($app) {
                return new ExtensionsInfoService($app['guzzle.client'], $app['extend.site'], $app['extend.urls'], $app['deprecated.php']);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
