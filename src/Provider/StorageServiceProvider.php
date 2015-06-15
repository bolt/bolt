<?php
namespace Bolt\Provider;

use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Storage;
use Bolt\Storage\EntityManager;
use Bolt\Storage\RecordModifier;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['storage.legacy'] = $app->share(
            function ($app) {
                return new Storage($app);
            }
        );

        $app['storage'] = $app->share(
            function ($app) {
                $storage = new EntityManager(
                    $app['db'],
                    $app['dispatcher'],
                    $app['storage.metadata'],
                    $app['logger.system']
                );
                $storage->setLegacyStorage($app['storage.legacy']);

                return $storage;
            }
        );

        $app['storage.typemap'] = [
            'Doctrine\DBAL\Types\StringType'   => 'Bolt\Storage\Field\Type\Text',
            'Doctrine\DBAL\Types\IntegerType'  => 'Bolt\Storage\Field\Type\Integer',
            'Doctrine\DBAL\Types\FloatType'    => 'Bolt\Storage\Field\Type\Float',
            'Doctrine\DBAL\Types\TextType'     => 'Bolt\Storage\Field\Type\Textarea',
            'Doctrine\DBAL\Types\DateType'     => 'Bolt\Storage\Field\Type\Date',
            'Doctrine\DBAL\Types\DateTimeType' => 'Bolt\Storage\Field\Type\Datetime',
            'checkbox'                         => 'Bolt\Storage\Field\Type\Checkbox',
            'date'                             => 'Bolt\Storage\Field\Type\Date',
            'datetime'                         => 'Bolt\Storage\Field\Type\Datetime',
            'file'                             => 'Bolt\Storage\Field\Type\File',
            'filelist'                         => 'Bolt\Storage\Field\Type\Filelist',
            'float'                            => 'Bolt\Storage\Field\Type\Float',
            'geolocation'                      => 'Bolt\Storage\Field\Type\Geolocation',
            'html'                             => 'Bolt\Storage\Field\Type\Html',
            'image'                            => 'Bolt\Storage\Field\Type\Image',
            'imagelist'                        => 'Bolt\Storage\Field\Type\Imagelist',
            'integer'                          => 'Bolt\Storage\Field\Type\Integer',
            'markdown'                         => 'Bolt\Storage\Field\Type\Markdown',
            'relation'                         => 'Bolt\Storage\Field\Type\Relation',
            'repeater'                         => 'Bolt\Storage\Field\Type\Repeater',
            'select'                           => 'Bolt\Storage\Field\Type\Select',
            'slug'                             => 'Bolt\Storage\Field\Type\Slug',
            'taxonomy'                         => 'Bolt\Storage\Field\Type\Taxonomy',
            'templateselect'                   => 'Bolt\Storage\Field\Type\Templateselect',
            'text'                             => 'Bolt\Storage\Field\Type\Text',
            'textarea'                         => 'Bolt\Storage\Field\Type\Textarea',
            'video'                            => 'Bolt\Storage\Field\Type\Video'
        ];

        $app['storage.metadata'] = $app->share(
            function ($app) {
                $meta = new MetadataDriver(
                    $app['schema'],
                    $app['config']->get('contenttypes'),
                    $app['config']->get('taxonomy'),
                    $app['storage.typemap']
                );
                return $meta;
            }
        );

        $app['storage.record_modifier'] = $app->share(
            function ($app) {
                $cm = new RecordModifier($app);

                return $cm;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
