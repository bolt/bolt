<?php
namespace Bolt\Provider;

use Bolt\EventListener\StorageEventListener;
use Bolt\Legacy\Storage;
use Bolt\Storage\Collection;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\ContentRequest;
use Bolt\Storage\Entity\Builder;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\Type\TemplateFieldsType;
use Bolt\Storage\FieldManager;
use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Storage\NamingStrategy;
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

        $app['storage.legacy_service'] = $app->share(
            function ($app) {
                return new ContentLegacyService($app);
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
                $storage->setEntityBuilder($app['storage.entity_builder']);
                $storage->setFieldManager($app['storage.field_manager']);
                $storage->setCollectionManager($app['storage.collection_manager']);

                foreach ($app['storage.repositories'] as $entity => $repo) {
                    $storage->setRepository($entity, $repo);
                }

                $storage->setDefaultRepositoryFactory($app['storage.content_repository']);

                return $storage;
            }
        );

        $app['storage.content_repository'] = $app->protect(
            function ($classMetadata) use ($app) {
                $repoClass = $app['storage.repository.default'];
                $repo = new $repoClass($app['storage'], $classMetadata);
                $repo->setLegacyService($app['storage.legacy_service']);

                return $repo;
            }
        );

        $app['storage.field_manager'] = $app->share(
            function ($app) {
                $manager = new FieldManager($app['storage.typemap'], $app['config']);

                foreach ($app['storage.typemap'] as $field) {
                    if (isset($app[$field])) {
                        $manager->setHandler($field, $app[$field]);
                    }
                }

                return $manager;
            }
        );

        // This uses a class name as the field types can optionally be injected
        // as services but the field manager only knows the class name, so we
        // use this to look up if there ss a service registered
        $app['Bolt\Storage\Field\Type\TemplateFieldsType'] = $app->protect(
            function ($mapping) use ($app) {
                $field = new TemplateFieldsType(
                    $mapping,
                    $app['storage'],
                    $app['templatechooser']
                );

                return $field;
            }
        );

        $app['storage.entity_builder'] = $app->share(
            function ($app) {
                $builder = new Builder($app['storage.metadata'], $app['storage.field_manager']);

                return $builder;
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
            'hidden'                           => 'Bolt\Storage\Field\Type\HiddenType',
            'html'                             => 'Bolt\Storage\Field\Type\HtmlType',
            'image'                            => 'Bolt\Storage\Field\Type\ImageType',
            'imagelist'                        => 'Bolt\Storage\Field\Type\ImageListType',
            'incomingrelation'                 => 'Bolt\Storage\Field\Type\IncomingRelationType',
            'integer'                          => 'Bolt\Storage\Field\Type\IntegerType',
            'markdown'                         => 'Bolt\Storage\Field\Type\MarkdownType',
            'relation'                         => 'Bolt\Storage\Field\Type\RelationType',
            'repeater'                         => 'Bolt\Storage\Field\Type\RepeaterType',
            'select'                           => 'Bolt\Storage\Field\Type\SelectType',
            'selectmultiple'                   => 'Bolt\Storage\Field\Type\SelectMultipleType',
            'slug'                             => 'Bolt\Storage\Field\Type\SlugType',
            'taxonomy'                         => 'Bolt\Storage\Field\Type\TaxonomyType',
            'templatefields'                   => 'Bolt\Storage\Field\Type\TemplateFieldsType',
            'templateselect'                   => 'Bolt\Storage\Field\Type\TemplateSelectType',
            'text'                             => 'Bolt\Storage\Field\Type\TextType',
            'textarea'                         => 'Bolt\Storage\Field\Type\TextAreaType',
            'video'                            => 'Bolt\Storage\Field\Type\VideoType',
        ];

        $app['storage.repositories'] = [
            'Bolt\Storage\Entity\Authtoken'  => 'Bolt\Storage\Repository\AuthtokenRepository',
            'Bolt\Storage\Entity\Cron'       => 'Bolt\Storage\Repository\CronRepository',
            'Bolt\Storage\Entity\FieldValue' => 'Bolt\Storage\Repository\FieldValueRepository',
            'Bolt\Storage\Entity\LogChange'  => 'Bolt\Storage\Repository\LogChangeRepository',
            'Bolt\Storage\Entity\LogSystem'  => 'Bolt\Storage\Repository\LogSystemRepository',
            'Bolt\Storage\Entity\Users'      => 'Bolt\Storage\Repository\UsersRepository',
        ];

        $app['storage.metadata'] = $app->share(
            function ($app) {
                $meta = new MetadataDriver(
                    $app['schema'],
                    $app['config']->get('contenttypes'),
                    $app['config']->get('taxonomy'),
                    $app['storage.typemap'],
                    $app['storage.namingstrategy']
                );

                return $meta;
            }
        );

        $app['storage.relations_collection'] = $app->protect(
            function () use ($app) {
                return new Collection\Relations([], $app['storage']);
            }
        );

        $app['storage.taxonomy_collection'] = $app->protect(
            function () use ($app) {
                return new Collection\Taxonomy([], $app['storage.metadata']);
            }
        );

        $app['storage.collection_manager'] = $app->share(
            function ($app) {
                $manager = new Collection\CollectionManager();
                $manager->setHandler('Bolt\Storage\Entity\Relations', $app['storage.relations_collection']);
                $manager->setHandler('Bolt\Storage\Entity\Taxonomy', $app['storage.taxonomy_collection']);

                return $manager;
            }
        );

        $app['storage.request.edit'] = $app->share(
            function ($app) {
                $cr = new ContentRequest\Edit(
                    $app['storage'],
                    $app['config'],
                    $app['users'],
                    $app['filesystem'],
                    $app['logger.system'],
                    $app['logger.flash']
                );

                return $cr;
            }
        );

        $app['storage.request.listing'] = $app->share(
            function ($app) {
                $cr = new ContentRequest\Listing($app['storage'], $app['config']);

                return $cr;
            }
        );

        $app['storage.request.modify'] = $app->share(
            function ($app) {
                $cr = new ContentRequest\Modify(
                    $app['storage'],
                    $app['users'],
                    $app['logger.system'],
                    $app['logger.flash']
                );

                return $cr;
            }
        );

        $app['storage.request.save'] = $app->share(
            function ($app) {
                $cr = new ContentRequest\Save(
                    $app['storage'],
                    $app['config'],
                    $app['users'],
                    $app['logger.change'],
                    $app['logger.system'],
                    $app['logger.flash'],
                    $app['url_generator.lazy']
                );

                return $cr;
            }
        );

        $app['storage.listener'] = $app->share(
            function () use ($app) {
                return new StorageEventListener(
                    $app['storage'],
                    $app['config'],
                    $app['schema'],
                    $app['url_generator.lazy'],
                    $app['logger.flash'],
                    $app['password_factory'],
                    $app['access_control.hash.strength']
                );
            }
        );

        $app['storage.namingstrategy'] = $app->share(
            function ($app) {
                $strategy = new NamingStrategy($app['config']->get('general/database/prefix', null));

                return $strategy;
            }
        );
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['storage.listener']);
    }
}
