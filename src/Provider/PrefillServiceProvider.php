<?php

namespace Bolt\Provider;

use Bolt\Storage\Database\Prefill;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PrefillServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['prefill.api_url'] = 'http://loripsum.net/api/';

        $app['prefill'] = $app->share(
            function ($app) {
                return new Prefill\ApiClient($app['guzzle.client'], $app['prefill.api_url']);
            }
        );

        $app['prefill.builder'] = $app->share(
            function ($app) {
                return new Prefill\Builder($app['storage'], $app['prefill.generator_factory'], 5);
            }
        );

        $app['prefill.default_field_values'] = $app->share(
            function () {
                return new ParameterBag([
                    'blocks' => [
                        'title' => 'About Us', 'Address', 'Search Teaser', '404 Not Found'
                    ],
                ]);
            }
        );

        $app['prefill.generator_factory'] = $app->protect(
            function ($contentTypeName) use ($app) {
                return new Prefill\RecordContentGenerator(
                    $contentTypeName,
                    $app['prefill'],
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
