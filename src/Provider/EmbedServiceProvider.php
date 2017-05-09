<?php

namespace Bolt\Provider;

use Bolt\Embed\Resolver;
use Bolt\Embed\GuzzleDispatcher;
use Embed\Embed;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Embed service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class EmbedServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['embed'] = $app->share(
            function ($app) {
                return new Resolver($app['embed.factory']);
            }
        );

        $app['embed.dispatcher'] = $app->share(
            function ($app) {
                return new GuzzleDispatcher($app['guzzle.client']);
            }
        );

        $app['embed.factory.config'] = $app->share(
            function () {
                return [];
            }
        );

        $app['embed.factory'] = $app->protect(
            function ($url, $options = []) use ($app) {
                $options = array_replace_recursive($app['embed.factory.config'], $options);
                /** @var \Embed\Adapters\Adapter $info */
                $info = Embed::create($url, $options, $app['embed.dispatcher']);

                return $info;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
