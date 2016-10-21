<?php

namespace Bolt\Provider;

use Bolt\Storage\Mapping\Definition;
use Bolt\Storage\Mapping\MappingManager;
use Pimple;
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
                $manager = new MappingManager($app['mapping.definition_factories'], $app['mapping.definitions'], $app['mapping.default']);
                return $manager;
            }
        );

        $app['mapping.definition_factories'] = $app->share(
            function (Application $app) {

                $definitions = new Pimple();

                $definitions['base'] = $app->protect(function ($name, array $data) {return new Definition($name, $data);});
                $definitions['date'] = $app->protect(function ($name, array $data) {return new Definition\Date($name, $data);});
                $definitions['file'] = $app->protect(function ($name, array $data, array $config) { return new Definition\File($name, $data, $config);});
                $definitions['number'] = $app->protect(function ($name, array $data) {return new Definition\Number($name, $data);});
                $definitions['image'] = $app->protect(function ($name, array $data, array $config) { return new Definition\Image($name, $data, $config); });
                $definitions['repeater'] = $app->protect(function ($name, array $data) use($app) {
                    $def = new Definition\Repeater($name, $data);
                    $def->setMappingManager($app['mapping']);
                    return $def;
                });
                $definitions['select'] = $app->protect(function ($name, array $data) {return new Definition\Select($name, $data);});
                $definitions['slug'] = $app->protect(function ($name, array $data) {return new Definition\Slug($name, $data);});
                $definitions['textarea'] = $app->protect(function ($name, array $data) {return new Definition\Textarea($name, $data);});

                return $definitions;
            }
        );

        $app['mapping.definitions'] = [
            'date'                         => 'date',
            'datetime'                     => 'date',
            'file'                         => 'file',
            'filelist'                     => 'file',
            'float'                        => 'number',
            'image'                        => 'image',
            'imagelist'                    => 'image',
            'integer'                      => 'number',
            'repeater'                     => 'repeater',
            'select'                       => 'select',
            'slug'                         => 'slug',
            'textarea'                     => 'textarea',
        ];

        $app['mapping.default'] = 'Bolt\Storage\Mapping\Definition';
    }

    public function boot(Application $app)
    {
    }
}
