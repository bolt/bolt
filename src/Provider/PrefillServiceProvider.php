<?php

namespace Bolt\Provider;

use Bolt\Collection\Bag;
use Bolt\Storage\Database\Prefill;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PrefillServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['prefill.api_url'] = 'http://loripsum.net/api/';

        $app['prefill'] = $app->share(
            function ($app) {
                return new Prefill\ApiClient($app['guzzle.client']);
            }
        );

        $app['prefill.image'] = $app->share(
            function ($app) {
                return new Prefill\ImageClient($app['guzzle.client']);
            }
        );

        $app['prefill.builder'] = $app->share(
            function ($app) {
                return new Prefill\Builder(
                    $app['storage'],
                    $app['prefill.generator_factory'],
                    5,
                    Bag::from($app['config']->get('contenttypes'))
                );
            }
        );

        $app['prefill.default_field_values'] = $app->share(
            function () {
                return new Bag([
                    'blocks' => [
                        'title' => 'About Us', 'Address', 'Search Teaser', '404 Not Found',
                    ],
                ]);
            }
        );

        $app['prefill.generator_factory'] = $app->protect(
            function ($contentTypeName) use ($app) {
                return new Prefill\RecordContentGenerator(
                    $contentTypeName,
                    $app['prefill'],
                    $app['prefill.image'],
                    $app['storage']->getRepository($contentTypeName),
                    $app['filesystem']->getFilesystem('files'),
                    $app['config']->get('taxonomy'),
                    $app['prefill.default_field_values']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
