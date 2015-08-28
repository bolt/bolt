<?php
namespace Bolt\Storage;

use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A default repository class that other repositories can inherit to provide more specific features.
 */
class Repository implements ObjectRepository
{
    /** @var EntityManager */
    public $em;
    /** @var ClassMetadata */
    public $_class;
    /** @var string */
    public $entityName;
    /** @var Hydrator */
    public $hydrator;
    /** @var Persister */
    public $persister;
    /** @var Loader */
    public $loader;

    /**
     * Initializes a new Repository.
     *
     * @param EntityManager  $em            The EntityManager to use.
     * @param ClassMetadata  $classMetadata The class descriptor.
     * @param Hydrator|null  $hydrator
     * @param Persister|null $persister
     * @param Loader|null    $loader
     */
    public function __construct($em, ClassMetadata $classMetadata, $hydrator = null, $persister = null, $loader = null)
    {
        $this->em = $em;
        $this->_class = $classMetadata;
        $this->entityName  = $classMetadata->getName();
        $this->setHydrator($hydrator ?: new Hydrator($classMetadata));
        $this->setPersister($persister ?: new Persister($classMetadata));
        $this->setLoader($loader ?: new Loader());
    }

    /**
     * Creates a new empty entity and passes the supplied data to the constructor.
     *
     * @param array $params
     *
     * @return Content
     */
    public function create($params = null)
    {
        $entityClass = $this->getClassName();
        return new $entityClass($params);
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        if (null === $alias) {
            $alias = $this->getAlias();
        }

        if (empty($alias)) {
            $select = "*";
        } else {
            $select = $alias.".*";
        }

        return $this->em->createQueryBuilder()
            ->select($select)
            ->from($this->getTableName(), $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        $qb = $this->getLoadQuery();
        $result = $qb->where($this->getAlias().'.id = :id')
            ->setParameter('id', $id)
            ->execute()
            ->fetch();

        if ($result) {
            return $this->hydrate($result, $qb);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->findWithCriteria($criteria, $orderBy, $limit, $offset);
        $qb->select('*');

        $result = $qb->execute()->fetchAll();

        if ($result) {
            return $this->hydrateAll($result, $qb);
        }

        return false;
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria The criteria.
     * @param array $orderBy
     *
     * @return object The object.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $qb = $this->findWithCriteria($criteria, $orderBy);
        $result = $qb->execute()->fetch();

        if ($result) {
            return $this->hydrate($result, $qb);
        }

        return false;
    }

    /**
     * Internal method to build a basic select, returns QB object.
     *
     * @param array $criteria
     * @param array $orderBy
     * @param int   $limit
     * @param int   $offset
     *
     * @return QueryBuilder
     */
    protected function findWithCriteria(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->getLoadQuery();
        foreach ($criteria as $col => $val) {
            $qb->andWhere($this->getAlias().".$col = :$col");
            $qb->setParameter(":$col", $val);
        }
        if ($orderBy) {
            $qb->orderBy($orderBy[0], $orderBy[1]);
        }
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if ($offset) {
            $qb->setFirstResult($offset);
        }
        return $qb;
    }

    /**
     * Method to hydrate and return a QueryBuilder query
     *
     * @return array Entity | false
     **/
    public function findWith(QueryBuilder $query)
    {
        $this->loader->load($query, $this->getClassMetadata());

        $result = $query->execute()->fetchAll();
        if ($result) {
            return $this->hydrateAll($result, $query);
        } else {
            return false;
        }
    }

    /**
     * Method to hydrate and return a single QueryBuilder result
     *
     * @return Entity | false
     **/
    public function findOneWith(QueryBuilder $query)
    {
        $this->loader->load($query, $this->getClassMetadata());
        $result = $query->execute()->fetch();
        if ($result) {
            return $this->hydrate($result, $query);
        } else {
            return false;
        }
    }

    /**
     * Method to execute query from a Bolt QueryInterface object
     * The query is passed to the pre-load handlers then built into a
     * QueryBuilder instance that can be executed.
     *
     * @param QueryInterface $query [description]
     *
     * @return [type] [description]
     */
    public function queryWith(QueryInterface $query)
    {
        $this->loader->query($query, $this->getClassMetadata());
        $queryBuilder = $query->build();

        return $this->findWith($queryBuilder);
    }

    /**
     * Internal method to initialise and return a QueryBuilder instance.
     * Note that the metadata fields will be passed the instance to modify where appropriate.
     *
     * @return QueryBuilder
     */
    protected function getLoadQuery()
    {
        $qb = $this->createQueryBuilder();
        $this->loader->load($qb, $this->getClassMetadata());

        return $qb;
    }

    /**
     * Deletes a single object.
     *
     * @param object $entity The entity to delete.
     *
     * @return boolean
     */
    public function delete($entity)
    {
        $event = new StorageEvent($entity);
        $this->event()->dispatch(StorageEvents::PRE_DELETE, $event);
        $qb = $this->em->createQueryBuilder()
            ->delete($this->getTableName())
            ->where("id = :id")
            ->setParameter('id', $entity->getId());

        $response = $qb->execute();
        $event = new StorageEvent($entity);
        $this->event()->dispatch(StorageEvents::POST_DELETE, $event);

        return $response;
    }

    /**
     * Saves a single object.
     *
     * @param object $entity The entity to delete.
     *
     * @return boolean
     */
    public function save($entity)
    {
        try {
            $existing = $entity->getId();
        } catch (\Exception $e) {
            $existing = false;
        }

        $event = new StorageEvent($entity, ['create' => $existing]);
        $this->event()->dispatch(StorageEvents::PRE_SAVE, $event);

        if ($existing) {
            $response = $this->update($entity);
        } else {
            $response = $this->insert($entity);
        }

        $this->event()->dispatch(StorageEvents::POST_SAVE, $event);

        return $response;
    }

    /**
     * Saves a new object into the database.
     *
     * @param object $entity The entity to insert.
     *
     * @return boolean
     */
    public function insert($entity)
    {
        $querySet = new QuerySet();
        $qb = $this->em->createQueryBuilder();
        $qb->insert($this->getTableName());
        $querySet->append($qb);
        $this->getPersister()->disableField('id');
        $this->persister->persist($querySet, $entity, $this->em);
        $this->getPersister()->enableField('id');

        return $querySet->execute();
    }

    /**
     * Updates an object into the database.
     *
     * @param object $entity The entity to update.
     *
     * @return bool
     */
    public function update($entity)
    {
        $querySet = new QuerySet();
        $qb = $this->em->createQueryBuilder();
        $qb->update($this->getTableName())
            ->where('id = :id')
            ->setParameter('id', $entity->getId());
        $querySet->append($qb);
        $this->persister->persist($querySet, $entity, $this->em);

        return $querySet->execute();
    }

    /**
     * Internal method to hydrate an Entity Object from fetched data.
     *
     * @param array        $data
     * @param QueryBuilder $qb
     *
     * @return mixed
     */
    protected function hydrate(array $data, QueryBuilder $qb)
    {
        $entity = $this->hydrator->create($data);
        $preArgs = new HydrationEvent(
            $data,
            ['entity' => $entity, 'repository' => $this]
        );
        $this->event()->dispatch(StorageEvents::PRE_HYDRATE, $preArgs);

        $entity = $this->hydrator->hydrate($entity, $data, $this->em);

        $postArgs = new HydrationEvent(
            $entity,
            ['data' => $data, 'repository' => $this]
        );
        $this->event()->dispatch(StorageEvents::POST_HYDRATE, $postArgs);

        return $entity;
    }

    /**
     * Internal method to hydrate an array of Entity Objects from fetched data.
     *
     * @param array        $data
     * @param QueryBuilder $qb
     *
     * @return mixed
     */
    protected function hydrateAll(array $data, QueryBuilder $qb)
    {
        $rows = [];
        foreach ($data as $row) {
            $rows[] = $this->hydrate($row, $qb);
        }

        return $rows;
    }

    /**
     * @param Hydrator $hydrator
     */
    public function setHydrator(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @param Persister $persister
     */
    public function setPersister(Persister $persister)
    {
        $this->persister = $persister;
    }

    /**
     * @return Persister $persister
     */
    public function getPersister()
    {
        return $this->persister;
    }

    /**
     * @param Loader $loader
     */
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->getEntityName();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getClassMetadata()->getTableName();
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->getClassMetadata()->getAliasName();
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Getter for class metadata
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Shortcut method to fetch the Event Dispatcher
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function event()
    {
        return $this->getEntityManager()->getEventManager();
    }
}
