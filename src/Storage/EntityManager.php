<?php

namespace Bolt\Storage;

use Bolt\Exception\InvalidRepositoryException;
use Bolt\Exception\StorageException;
use Bolt\Legacy\Storage;
use Bolt\Storage\Collection\CollectionManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Repository\ContentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages all loaded entities across application, provides access to Repository
 * Classes.
 *
 * Legacy methods:
 *
 * @method void  publishTimedRecords($contenttype)
 * @method void  depublishExpiredRecords($contenttype)
 */
class EntityManager implements EntityManagerInterface
{
    /** @var Connection */
    protected $conn;
    /** @var EventDispatcherInterface */
    protected $eventManager;
    /** @var MetadataDriver */
    protected $mapping;
    /** @var LoggerInterface */
    protected $logger;
    /** @var Entity\Builder */
    protected $builder;
    /** @var FieldManager */
    protected $fieldManager;
    /** @var CollectionManager */
    protected $collectionManager;
    /** @var array */
    protected $repositories = [];
    /** @var array */
    protected $aliases = [];
    /** @var Storage */
    protected $legacyStorage;
    /** @var callable */
    protected $defaultRepositoryFactory;
    /** @var  ContentLegacyService */
    protected $legacyService;
    /** @var Query */
    private $queryService;

    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given EventManager.
     *
     * @param \Doctrine\DBAL\Connection $conn
     * @param EventDispatcherInterface  $eventManager
     * @param MetadataDriver            $mapping
     * @param LoggerInterface           $logger
     */
    public function __construct(Connection $conn, EventDispatcherInterface $eventManager, MetadataDriver $mapping, LoggerInterface $logger = null)
    {
        $this->conn = $conn;
        $this->eventManager = $eventManager;
        $this->mapping = $mapping;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->conn);
    }

    /**
     * @return ExpressionBuilder
     */
    public function createExpressionBuilder()
    {
        return new ExpressionBuilder($this->conn);
    }

    /**
     * Creates an entity of the given class, with the data supplied.
     *
     * @param string                 $className The type of entity to create
     * @param array                  $data      The data to use to hydrate the new entity
     * @param ClassMetadataInterface $metadata
     *
     * @throws InvalidRepositoryException
     *
     * @return Entity\Entity
     */
    public function create($className, $data, ClassMetadataInterface $metadata = null)
    {
        $repo = $this->getRepository($className);

        return $repo->create($data, $metadata);
    }

    /**
     * Get an entity builder instance for a given class.
     *
     * @param string        $className
     * @param ClassMetadata $classMetadata
     *
     * @return Entity\Builder
     */
    public function getEntityBuilder($className = null, ClassMetadata $classMetadata = null)
    {
        $builder = new Entity\Builder($this->getMapper(), $this->getFieldManager());

        if ($className !== null) {
            $builder->setClass($className);
        }

        if ($classMetadata !== null) {
            $builder->setClassMetadata($classMetadata);
        }

        return $builder;
    }

    /**
     * Set an entity builder instance.
     *
     * @param Entity\Builder $builder
     */
    public function setEntityBuilder(Entity\Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return FieldManager
     */
    public function getFieldManager()
    {
        $manager = $this->fieldManager;
        $manager->setEntityManager($this);

        return $manager;
    }

    /**
     * @param FieldManager $fieldManager
     */
    public function setFieldManager(FieldManager $fieldManager)
    {
        $this->fieldManager = $fieldManager;
    }

    /**
     * @return CollectionManager
     */
    public function getCollectionManager()
    {
        $manager = $this->collectionManager;
        $manager->setEntityManager($this);

        return $manager;
    }

    /**
     * @param CollectionManager $collectionManager
     */
    public function setCollectionManager(CollectionManager $collectionManager)
    {
        $this->collectionManager = $collectionManager;
    }

    /**
     * Shorthand access method to create collection. Consults aliases to allow short names.
     *
     * @param string|Entity\Entity $className
     *
     * @return ArrayCollection
     */
    public function createCollection($className)
    {
        $className = (string) $className;
        if (array_key_exists($className, $this->aliases)) {
            $className = $this->aliases[$className];
        }

        return $this->getCollectionManager()->create($className);
    }

    /**
     * Finds an object by its identifier.
     *
     * This is just a convenient shortcut for getRepository($className)->find($id).
     *
     * @param string     $className class name of the object to find
     * @param int|string $id        identity of the object to find
     *
     * @throws InvalidRepositoryException
     *
     * @return object the found object
     */
    public function find($className, $id)
    {
        $repo = $this->getRepository($className);

        return $repo->find($id);
    }

    /**
     * The object will be entered into the database as a result of this operation.
     *
     * @param object $object the instance to persist to storage
     *
     * @throws InvalidRepositoryException
     *
     * @return bool
     */
    public function save($object)
    {
        if (method_exists($object, 'getContenttype')) {
            $entityName = $object->getContenttype();
        } else {
            $entityName = get_class($object);
        }
        $repo = $this->getRepository($entityName);

        return $repo->save($object);
    }

    /**
     * Removes an object instance.
     *
     * Passed in object will be removed from the database as a result of this operation.
     *
     * @param object $object the object instance to remove
     *
     * @throws InvalidRepositoryException
     *
     * @return bool
     */
    public function delete($object)
    {
        $entityName = get_class($object);
        $repo = $this->getRepository($entityName);

        return $repo->delete($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        /** @var Repository $repo */
        $repo = null;

        $className = (string) $className;
        if (array_key_exists($className, $this->aliases)) {
            $className = $this->aliases[$className];
        }

        try {
            $classMetadata = $this->getMapper()->loadMetadataForClass($className);
        } catch (StorageException $e) {
            throw new InvalidRepositoryException("Attempted to load repository for invalid class or alias: $className. Check that the class, alias or contenttype definition is correct.");
        }

        if (array_key_exists($classMetadata->getName(), $this->repositories)) {
            $repoClass = $this->repositories[$classMetadata->getName()];
            if (is_callable($repoClass)) {
                $repo = call_user_func($repoClass, $this, $classMetadata);
            } else {
                $repo = new $repoClass($this, $classMetadata);
            }
        }

        if ($repo === null) {
            foreach ($this->aliases as $alias => $namespace) {
                $full = str_replace($alias, $namespace, $className);

                if (array_key_exists($full, $this->repositories)) {
                    $classMetadata = $this->getMapper()->loadMetadataForClass($full);
                    $repoClass = $this->repositories[$full];

                    $repo = new $repoClass($this, $classMetadata);
                }
            }
        }

        /*
         * The metadata driver can also attempt to resolve an alias for us.
         * For now we are hardcoding the link between a content entity and
         * the content repository, but in time this should be a metadata level
         * configuration.
         */
        if ($repo === null && $this->getMapper()->resolveClassName($className) === Entity\Content::class) {
            $repo = $this->getDefaultRepositoryFactory($classMetadata);
        }

        /*
         * If the fetched metadata isn't mapped to a specific entity then we treat
         * it as a generic Content repo
         */
        if ($repo === null && in_array($className, $this->getMapper()->getUnmapped())) {
            $repo = $this->getDefaultRepositoryFactory($classMetadata);
        }

        if ($repo === null) {
            $repo = new Repository($this, $classMetadata);
        }

        if ($repo instanceof Repository\ContentRepository && $this->legacyService !== null) {
            /** @var ContentRepository $repo */
            $repo->setLegacyService($this->legacyService);
        }

        return $repo;
    }

    /**
     * Sets a custom repository class for an entity.
     *
     * @param string $entityName
     * @param string $repositoryClass
     */
    public function setRepository($entityName, $repositoryClass)
    {
        $this->repositories[$entityName] = $repositoryClass;
    }

    /**
     * Sets a default repository factory that can handle metadata that is not
     * mapped to a specific entity.
     *
     * @param callable $factory
     */
    public function setDefaultRepositoryFactory(callable $factory)
    {
        $this->defaultRepositoryFactory = $factory;
    }

    /**
     * Returns the default repository factory set on this object.
     *
     * @param ClassMetadataInterface $classMetadata
     *
     * @return callable $factory
     */
    public function getDefaultRepositoryFactory($classMetadata)
    {
        if (!is_callable($this->defaultRepositoryFactory)) {
            throw new \RuntimeException('Unable to handle unmapped data without a defaultRepositoryFactory set', 1);
        }

        $factory = $this->defaultRepositoryFactory;

        return $factory($classMetadata);
    }

    /**
     * Gets the DBAL Driver Connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Gets the Event Manager.
     *
     * @return EventDispatcherInterface
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Gets the Class Metadata Driver.
     *
     * @return MetadataDriver
     */
    public function getMapper()
    {
        return $this->mapping;
    }

    /**
     * Registers shorter alias access for Entities.
     *
     * For example ->addEntityAlias('user', 'Project\Bundle\Module\Entity\User')
     * would allow ->getRepository('user')
     *
     * @param string $alias
     * @param string $namespace
     */
    public function addEntityAlias($alias, $namespace)
    {
        $this->aliases[$alias] = $namespace;
    }

    /**
     * Returns a proxy to the legacy storage service.
     *
     * @return Storage
     */
    public function legacy()
    {
        return $this->legacyStorage;
    }

    /**
     * Sets the LegacyRepository.
     *
     * @param Storage $storage
     */
    public function setLegacyStorage(Storage $storage)
    {
        $this->legacyStorage = $storage;
    }

    /**
     * Sets the LegacyRepository.
     *
     * @param ContentLegacyService $service
     */
    public function setLegacyService(ContentLegacyService $service)
    {
        $this->legacyService = $service;
    }

    /**
     * Getter for logger object.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function setQueryService($queryService)
    {
        $this->queryService = $queryService;
    }

    /******* Deprecated functions ******/

    /**
     * Magic call method acts as a catchall proxy to the legacy repository.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        if ($this->legacyStorage !== null) {
            return call_user_func_array([$this->legacy(), $method], $args);
        } elseif ($this->queryService !== null) {
            return call_user_func_array([$this->queryService, $method], $args);
        }

        throw new \Exception('Legacy service nor Query service loaded');
    }

    /**
     * Note that this method is explicitly defined here because the magic method above cannot
     * pass dynamic variables by reference.
     *
     * @param string $textquery
     * @param array  $parameters
     * @param array  $pager
     * @param array  $whereparameters
     *
     * @deprecated
     *
     * @return mixed
     */
    public function getContent($textquery, $parameters = [], &$pager = [], $whereparameters = [])
    {
        if ($this->legacyStorage !== null) {
            return $this->legacy()->getContent($textquery, $parameters, $pager, $whereparameters);
        } elseif ($this->queryService !== null) {
            return $this->queryService->getContent($textquery, array_merge($parameters, $whereparameters));
        }

        throw new \Exception('Legacy service nor Query service loaded');
    }

    /**
     * Drop in replacement for the legacy storage getContentType method.
     *
     * @param string $alias
     *
     * @return Mapping\ContentType|array
     */
    public function getContentType($alias)
    {
        if ($this->legacyStorage !== null) {
            return $this->legacy()->getContentType($alias);
        }

        return $this->mapping->createContentType($alias);
    }
}
