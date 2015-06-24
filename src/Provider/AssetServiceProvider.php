<?php
namespace Bolt\Provider;

use Bolt\Asset;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * HTML asset service providers.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 */
class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['asset.salt.factory'] = $app->protect(function () use ($app) {
            return $app['randomgenerator']->generateString(10);
        });

        $app['asset.salt'] = $app->share(
            function ($app) {
                $path = $app['resources']->getPath('cache/.assetsalt');
                if (is_readable($path)) {
                    $salt = file_get_contents($path);
                } else {
                    $salt = $app['asset.salt.factory']();
                    file_put_contents($path, $salt);
                }

                return $salt;
            }
        );

        $app['asset.file.hash'] = $app->protect(function ($fileName) use ($app) {
            $fullPath = $app['resources']->getPath('root') . '/' . $fileName;

            if (is_readable($fullPath)) {
                return substr(md5($app['asset.salt'] . (string) filemtime($fullPath)), 0, 10);
            } elseif (is_readable($fileName)) {
                return substr(md5($app['asset.salt'] . (string) filemtime($fileName)), 0, 10);
            }
        });

        $app['asset.injector'] = $app->share(
            function () {
                $snippets = new Asset\Injector();

                return $snippets;
            }
        );

        $app['asset.queue.file'] = $app->share(
            function ($app) {
                $queue = new Asset\File\Queue($app);

                return $queue;
            }
        );

        $app['asset.queue.snippet'] = $app->share(
            function ($app) {
                $queue = new Asset\Snippet\Queue($app);

                return $queue;
            }
        );

        $app['asset.queues'] = $app->share(
            function ($app) {
                return [
                    $app['asset.queue.file'],
                    $app['asset.queue.snippet']
                ];
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
