<?php
namespace Bolt\Provider;

use Bolt\Assets;
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
        $app['assets.salt.factory'] = $app->protect(function () use ($app) {
            return $app['randomgenerator']->generateString(10);
        });

        $app['assets.salt'] = $app->share(
            function ($app) {
                $path = $app['resources']->getPath('cache/.assetsalt');
                if (is_readable($path)) {
                    $salt = file_get_contents($path);
                } else {
                    $salt = $app['assets.salt.factory']();
                    file_put_contents($path, $salt);
                }

                return $salt;
            }
        );

        $app['assets.file.hash'] = $app->protect(function ($fileName) use ($app) {
            $fullPath = $app['resources']->getPath('root') . '/' . $fileName;
            if (is_readable($fullPath)) {
                return substr(md5($app['assets.salt'] . filemtime($fullPath)), 0, 10);
            }

            return substr(md5($app['assets.salt'] . $fileName), 0, 10);
        });

        $app['assets.injector'] = $app->share(
            function () {
                $snippets = new Assets\Injector();

                return $snippets;
            }
        );

        $app['assets.queue.file'] = $app->share(
            function ($app) {
                $queue = new Assets\Files\Queue($app);

                return $queue;
            }
        );

        $app['assets.queue.snippet'] = $app->share(
            function ($app) {
                $queue = new Assets\Snippets\Queue($app);

                return $queue;
            }
        );

        $app['assets.queues'] = $app->share(
            function ($app) {
                return [
                    $app['assets.queue.file'],
                    $app['assets.queue.snippet']
                ];
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
