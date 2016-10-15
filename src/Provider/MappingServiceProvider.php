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
                $manager = new MappingManager($app['mapping.definitions'], $app['mapping.default'], $app['config']->get('general'));

                return $manager;
            }
        );

        $app['mapping.definitions'] = [
            'file'                         => 'Bolt\Storage\Mapping\Definition\File',
            'filelist'                     => 'Bolt\Storage\Mapping\Definition\File',
            'image'                        => 'Bolt\Storage\Mapping\Definition\Image',
            'imagelist'                    => 'Bolt\Storage\Mapping\Definition\Image',
            'select'                       => 'Bolt\Storage\Mapping\Definition\Select',
            'slug'                         => 'Bolt\Storage\Mapping\Definition\Slug',
            'repeater'                     => 'Bolt\Storage\Mapping\Definition\Repeater',
        ];

        $app['mapping.default'] = 'Bolt\Storage\Mapping\Definition';
    }

    public function boot(Application $app)
    {
    }
}
