<?php

namespace Bolt\Provider;

use Bolt\Storage\Mapping\MappingManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MappingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['mapping'] = $app->share(
            function ($app) {
                $manager = new MappingManager($app['mapping.definitions'], $app['mapping.default']);

                return $manager;
            }
        );

        $app['mapping.definitions'] = [
            'slug'                         => 'Bolt\Storage\Mapping\Definition\Slug',
            'repeater'                     => 'Bolt\Storage\Mapping\Definition\Repeater',
        ];

        $app['mapping.default'] = 'Bolt\Storage\Mapping\Definition';
    }

    public function boot(Application $app)
    {
    }
}
