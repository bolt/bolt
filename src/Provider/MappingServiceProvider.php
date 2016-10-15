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
            'date'                         => 'Bolt\Storage\Mapping\Definition\Date',
            'datetime'                     => 'Bolt\Storage\Mapping\Definition\Date',
            'file'                         => 'Bolt\Storage\Mapping\Definition\File',
            'filelist'                     => 'Bolt\Storage\Mapping\Definition\File',
            'float'                        => 'Bolt\Storage\Mapping\Definition\Number',
            'image'                        => 'Bolt\Storage\Mapping\Definition\Image',
            'imagelist'                    => 'Bolt\Storage\Mapping\Definition\Image',
            'integer'                      => 'Bolt\Storage\Mapping\Definition\Number',
            'repeater'                     => 'Bolt\Storage\Mapping\Definition\Repeater',
            'select'                       => 'Bolt\Storage\Mapping\Definition\Select',
            'slug'                         => 'Bolt\Storage\Mapping\Definition\Slug',
            'textarea'                     => 'Bolt\Storage\Mapping\Definition\Textarea',
        ];

        $app['mapping.default'] = 'Bolt\Storage\Mapping\Definition';
    }

    public function boot(Application $app)
    {
    }
}
