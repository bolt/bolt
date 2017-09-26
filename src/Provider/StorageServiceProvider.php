<?php

namespace Bolt\Provider;

use Bolt\Configuration\ConfigurationValueProxy;
use Bolt\EventListener\StorageEventListener;
use Bolt\Legacy\Storage;
use Bolt\Storage\Collection;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\ContentRequest;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\EventProcessor;
use Bolt\Storage\Field;
use Bolt\Storage\FieldManager;
use Bolt\Storage\LazyEntityManager;
use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Storage\NamingStrategy;
use Bolt\Storage\Repository;
use Doctrine\DBAL;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

class StorageServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['storage.legacy'] = function ($app) {
            return new Storage($app);
        };

        $app['storage.legacy_service'] = function ($app) {
            return new ContentLegacyService($app);
        };

        $app['storage.lazy'] = function ($app) {
            return new LazyEntityManager(
                function () use ($app) {
                    return $app['storage'];
                }
            );
        };

        $app['storage'] = function ($app) {
            $storage = new EntityManager(
                $app['db'],
                $app['dispatcher'],
                $app['storage.metadata'],
                $app['logger.system']
            );
            $storage->setLegacyService($app['storage.legacy_service']);
            $storage->setLegacyStorage($app['storage.legacy']);
            $storage->setEntityBuilder($app['storage.entity_builder']);
            $storage->setFieldManager($app['storage.field_manager']);
            $storage->setCollectionManager($app['storage.collection_manager']);

            foreach ($app['storage.repositories'] as $entity => $repo) {
                $storage->setRepository($entity, $repo);
            }

            $storage->setDefaultRepositoryFactory($app['storage.content_repository']);

            return $storage;
        };

        $app['storage.content_repository'] = $app->protect(
            function ($classMetadata) use ($app) {
                $repoClass = $app['storage.repository.default'];
                /** @var Repository\ContentRepository $repo */
                $repo = new $repoClass($app['storage'], $classMetadata);

                return $repo;
            }
        );

        $app['storage.field_sanitiser'] = function ($app) {
            $allowedTags = $app['config']->get('general/htmlcleaner/allowed_tags', []);
            $allowedAttributes = $app['config']->get('general/htmlcleaner/allowed_attributes', []);
            $allowedWyswig = $app['config']->get('general/wysiwyg', []);

            return new Field\Sanitiser\Sanitiser($allowedTags, $allowedAttributes, $allowedWyswig);
        };

        $app['storage.field_manager'] = function ($app) {
            $manager = new FieldManager($app['storage.typemap'], $app['config'], $app['storage.field_sanitiser']);

            foreach ($app['storage.typemap'] as $field) {
                if (isset($app[$field])) {
                    $manager->setHandler($field, $app[$field]);
                }
            }

            return $manager;
        };

        // This uses a class name as the field types can optionally be injected
        // as services but the field manager only knows the class name, so we
        // use this to look up if there ss a service registered
        $app[Field\Type\TemplateFieldsType::class] = $app->protect(
            function ($mapping) use ($app) {
                $field = new Field\Type\TemplateFieldsType(
                    $mapping,
                    $app['storage'],
                    $app['templatechooser'],
                    $app['twig']
                );

                return $field;
            }
        );

        $app['storage.entity_builder'] = function ($app) {
            $builder = new Entity\Builder($app['storage.metadata'], $app['storage.field_manager']);

            return $builder;
        };

        $app['storage.repository.default'] = Repository\ContentRepository::class;

        $app['storage.typemap'] = [
            DBAL\Types\StringType::class   => Field\Type\TextType::class,
            DBAL\Types\IntegerType::class  => Field\Type\IntegerType::class,
            DBAL\Types\FloatType::class    => Field\Type\FloatType::class,
            DBAL\Types\TextType::class     => Field\Type\TextAreaType::class,
            DBAL\Types\DateType::class     => Field\Type\DateType::class,
            DBAL\Types\DateTimeType::class => Field\Type\DateTimeType::class,
            'block'                        => Field\Type\BlockType::class,
            'checkbox'                     => Field\Type\CheckboxType::class,
            'date'                         => Field\Type\DateType::class,
            'datetime'                     => Field\Type\DateTimeType::class,
            'file'                         => Field\Type\FileType::class,
            'filelist'                     => Field\Type\FileListType::class,
            'float'                        => Field\Type\FloatType::class,
            'geolocation'                  => Field\Type\GeolocationType::class,
            'hidden'                       => Field\Type\HiddenType::class,
            'html'                         => Field\Type\HtmlType::class,
            'image'                        => Field\Type\ImageType::class,
            'imagelist'                    => Field\Type\ImageListType::class,
            'incomingrelation'             => Field\Type\IncomingRelationType::class,
            'integer'                      => Field\Type\IntegerType::class,
            'markdown'                     => Field\Type\MarkdownType::class,
            'relation'                     => Field\Type\RelationType::class,
            'repeater'                     => Field\Type\RepeaterType::class,
            'select'                       => Field\Type\SelectType::class,
            'selectmultiple'               => Field\Type\SelectMultipleType::class,
            'slug'                         => Field\Type\SlugType::class,
            'taxonomy'                     => Field\Type\TaxonomyType::class,
            'templatefields'               => Field\Type\TemplateFieldsType::class,
            'templateselect'               => Field\Type\TemplateSelectType::class,
            'text'                         => Field\Type\TextType::class,
            'textarea'                     => Field\Type\TextAreaType::class,
            'video'                        => Field\Type\VideoType::class,
        ];

        $app['storage.repositories'] = [
            Entity\Authtoken::class  => Repository\AuthtokenRepository::class,
            Entity\Cron::class       => Repository\CronRepository::class,
            Entity\FieldValue::class => Repository\FieldValueRepository::class,
            Entity\LogChange::class  => Repository\LogChangeRepository::class,
            Entity\LogSystem::class  => Repository\LogSystemRepository::class,
            Entity\Users::class      => Repository\UsersRepository::class,
        ];

        $app['storage.metadata'] = function ($app) {
            $meta = new MetadataDriver(
                $app['schema'],
                $app['storage.config.contenttypes'],
                $app['storage.config.taxonomy'],
                $app['storage.typemap'],
                $app['storage.namingstrategy']
            );
            $meta->setGeneralConfig($app['config']);

            return $meta;
        };

        $app['storage.config.contenttypes'] = function ($app) {
            return new ConfigurationValueProxy($app['config'], 'contenttypes');
        };

        $app['storage.config.taxonomy'] = function ($app) {
            return new ConfigurationValueProxy($app['config'], 'taxonomy');
        };

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

        $app['storage.collection_manager'] = function ($app) {
            $manager = new Collection\CollectionManager();
            $manager->setHandler(Entity\Relations::class, $app['storage.relations_collection']);
            $manager->setHandler(Entity\Taxonomy::class, $app['storage.taxonomy_collection']);

            return $manager;
        };

        $app['storage.request.edit'] = function ($app) {
            $cr = new ContentRequest\Edit(
                $app['storage'],
                $app['config'],
                $app['users'],
                $app['filesystem'],
                $app['logger.system'],
                $app['logger.flash']
            );
            // @deprecated Temporary and to be removed circa Bolt 3.5.
            $cr->setQueryHandler($app['query']);

            return $cr;
        };

        $app['storage.request.listing'] = function ($app) {
            $cr = new ContentRequest\Listing($app['storage'], $app['query'], $app['config'], $app['pager']);

            return $cr;
        };

        $app['storage.request.modify'] = function ($app) {
            $cr = new ContentRequest\Modify(
                $app['storage'],
                $app['users'],
                $app['logger.system'],
                $app['logger.flash']
            );

            return $cr;
        };

        $app['storage.request.save'] = function ($app) {
            $cr = new ContentRequest\Save(
                $app['storage'],
                $app['config'],
                $app['users'],
                $app['logger.change'],
                $app['logger.system'],
                $app['logger.flash'],
                $app['url_generator.lazy'],
                $app['slugify']
            );

            return $cr;
        };

        $app['storage.listener'] = function () use ($app) {
            return new StorageEventListener(
                $app['storage.lazy'],
                $app['storage.event_processor.timed'],
                $app['schema.lazy'],
                $app['url_generator.lazy'],
                $app['logger.flash'],
                $app['password_hash.manager'],
                $app['access_control.hash.strength'],
                !$app['config']->get('general/performance/timed_records/use_cron', false)
            );
        };

        $app['storage.event_processor.timed'] = function ($app) {
            $contentTypes = array_keys($app['config']->get('contenttypes', []));
            $interval = $app['config']->get('general/performance/timed_records/interval');

            return new EventProcessor\TimedRecord(
                $contentTypes,
                $app['storage.lazy'],
                $app['cache'],
                $app['dispatcher'],
                $app['logger.system'],
                $interval
            );
        };

        $app['storage.namingstrategy'] = function ($app) {
            $strategy = new NamingStrategy($app['config']->get('general/database/prefix', null));

            return $strategy;
        };
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['storage.listener']);
        $dispatcher->addSubscriber($app['storage.config.contenttypes']);
        $dispatcher->addSubscriber($app['storage.config.taxonomy']);
    }
}
