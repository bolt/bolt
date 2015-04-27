<?php

namespace Bolt\Provider;

use Bolt\Storage;
use Bolt\Storage\EntityManager;
use Bolt\Mapping\MetadataDriver;
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
        
        
        $app['storage.typemap'] = array(
            'Doctrine\DBAL\Types\StringType' => 'Bolt\Field\Type\Text',
            'Doctrine\DBAL\Types\IntegerType' => 'Bolt\Field\Type\Integer',
            'Doctrine\DBAL\Types\FloatType' => 'Bolt\Field\Type\Float',
            'Doctrine\DBAL\Types\TextType' => 'Bolt\Field\Type\Textarea',
            'Doctrine\DBAL\Types\DateType' => 'Bolt\Field\Type\Date',
            'Doctrine\DBAL\Types\DateTimeType' => 'Bolt\Field\Type\DateTime',
            'checkbox' => 'Bolt\Field\Type\Checkbox',
            'date' => 'Bolt\Field\Type\Date',
            'datetime' => 'Bolt\Field\Type\DateTime',
            'file' => 'Bolt\Field\Type\File',
            'filelist' => 'Bolt\Field\Type\Filelist',
            'float' => 'Bolt\Field\Type\Float',
            'geolocation' => 'Bolt\Field\Type\Geolocation',
            'html' => 'Bolt\Field\Type\Html',
            'image' => 'Bolt\Field\Type\Image',
            'imagelist' => 'Bolt\Field\Type\Imagelist',
            'integer' => 'Bolt\Field\Type\Integer',
            'markdown' => 'Bolt\Field\Type\Markdown',
            'relation' => 'Bolt\Field\Type\Relation',
            'repeater' => 'Bolt\Field\Type\Repeater',
            'select' => 'Bolt\Field\Type\Select',
            'slug' => 'Bolt\Field\Type\Slug',
            'taxonomy' => 'Bolt\Field\Type\Taxonomy',
            'templateselect' => 'Bolt\Field\Type\Templateselect',
            'text' => 'Bolt\Field\Type\Text',
            'textarea' => 'Bolt\Field\Type\Textarea',
            'video' => 'Bolt\Field\Type\Video'
        );
        
        $app['storage.metadata'] = $app->share(
            function ($app) {
                $meta = new MetadataDriver(
                    $app['integritychecker'], 
                    $app['config']->get('contenttypes'),
                    $app['storage.typemap']
                );
                return $meta;
            }
        );
        
    }

    public function boot(Application $app)
    {
    }
}
