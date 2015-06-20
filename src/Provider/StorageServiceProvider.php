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
                $storage->setDefaultRepositoryFactory(
                    function($classMetadata) use ($app) {
                        $repoClass = $app['storage.repository.default'];
                        $repo = new $repoClass($app['storage'], $classMetadata);

                        return $repo;
                    }
                );

                return $storage;
            }
        );
        
        $app['storage.repository.default'] = 'Bolt\Storage\Repository\ContentRepository';

        $app['storage.typemap'] = [
            'Doctrine\DBAL\Types\StringType'   => 'Bolt\Storage\Field\Type\TextType',
            'Doctrine\DBAL\Types\IntegerType'  => 'Bolt\Storage\Field\Type\IntegerType',
            'Doctrine\DBAL\Types\FloatType'    => 'Bolt\Storage\Field\Type\FloatType',
            'Doctrine\DBAL\Types\TextType'     => 'Bolt\Storage\Field\Type\TextAreaType',
            'Doctrine\DBAL\Types\DateType'     => 'Bolt\Storage\Field\Type\DateType',
            'Doctrine\DBAL\Types\DateTimeType' => 'Bolt\Storage\Field\Type\DateTimeType',
            'checkbox'                         => 'Bolt\Storage\Field\Type\CheckboxType',
            'date'                             => 'Bolt\Storage\Field\Type\DateType',
            'datetime'                         => 'Bolt\Storage\Field\Type\DateTimeType',
            'file'                             => 'Bolt\Storage\Field\Type\FileType',
            'filelist'                         => 'Bolt\Storage\Field\Type\FileListType',
            'float'                            => 'Bolt\Storage\Field\Type\FloatType',
            'geolocation'                      => 'Bolt\Storage\Field\Type\GeolocationType',
            'html'                             => 'Bolt\Storage\Field\Type\HtmlType',
            'image'                            => 'Bolt\Storage\Field\Type\ImageType',
            'imagelist'                        => 'Bolt\Storage\Field\Type\ImageListType',
            'integer'                          => 'Bolt\Storage\Field\Type\IntegerType',
            'markdown'                         => 'Bolt\Storage\Field\Type\MarkdownType',
            'relation'                         => 'Bolt\Storage\Field\Type\RelationType',
            'repeater'                         => 'Bolt\Storage\Field\Type\RepeaterType',
            'select'                           => 'Bolt\Storage\Field\Type\SelectType',
            'slug'                             => 'Bolt\Storage\Field\Type\SlugType',
            'taxonomy'                         => 'Bolt\Storage\Field\Type\TaxonomyType',
            'templateselect'                   => 'Bolt\Storage\Field\Type\TemplateSelectType',
            'text'                             => 'Bolt\Storage\Field\Type\TextType',
            'textarea'                         => 'Bolt\Storage\Field\Type\TextAreaType',
            'video'                            => 'Bolt\Storage\Field\Type\VideoType'
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
