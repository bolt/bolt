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
        $app['assets.injector'] = $app->share(
            function () {
                $snippets = new Assets\Injector();

                return $snippets;
            }
        );

        $app['assets.queue.snippet'] = $app->share(
            function ($app) {
                $queue = new Assets\Snippets\Queue($app);

                return $queue;
            }
        );

        $app['assets.queue.file'] = $app->share(
            function ($app) {
                $queue = new Assets\File\Queue($app);

                return $queue;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
