<?php

namespace Bolt\Provider;

use Bolt\Embed\GuzzleDispatcher;
use Bolt\Embed\Resolver;
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
                return new GuzzleDispatcher($app['guzzle.client'], $app['guzzle.handler_stack']);
            }
        );

        $app['embed.factory.config'] = $app->share(
            function ($app) {
                return [
                    'min_image_width'     => 60,
                    'min_image_height'    => 60,
                    'images_blacklist'    => null,
                    'choose_bigger_image' => false,
                    'html'                => [
                        'max_images'      => 10,
                        'external_images' => false,
                    ],
                    'oembed' => [
                        'parameters' => [],
                    ],
                    'google' => [
                        'key' => $app['config']->get('general/google_api_key'),
                    ],
                ];
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
