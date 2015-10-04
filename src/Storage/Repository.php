<?php

namespace Bolt\Storage;

use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Storage\Entity\Builder;
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
    /** @var Builder */
    public $builder;

    /**
     * Initializes a new Repository.
     *
     * @param EntityManager $em            The EntityManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($em, ClassMetadata $classMetadata)
    {
        $this->em = $em;
        $this->_class = $classMetadata;
        $this->entityName = $classMetadata->getName();
    }

    /**
     * Creates a new empty entity and passes the supplied data to the constructor.
     *
     * @param array $params
     *
     * @return Content
     */
    public function create($params = [], ClassMetadata $metadata = null)
    {
        $entity = $this->getEntityBuilder()->create($params, $metadata);
        $preEventArgs = new HydrationEvent($params, ['entity' => $entity, 'repository' => $this]);
        $this->event()->dispatch(StorageEvents::PRE_HYDRATE, $preEventArgs);
        $this->event()->dispatch(StorageEvents::POST_HYDRATE, $preEventArgs);

        return $entity;
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
            $select = '*';
        } else {
            $select = $alias.'.*';
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
     * Method to hydrate and return a QueryBuilder query.
     *
     * @return array Entity | false
     **/
    public function findWith(QueryBuilder $query)
    {
        $this->load($query);

        $result = $query->execute()->fetchAll();
        if ($result) {
            return $this->hydrateAll($result, $query);
        } else {
            return false;
        }
    }

    /**
     * Method to hydrate and return a single QueryBuilder result.
     *
     * @return Entity | false
     **/
    public function findOneWith(QueryBuilder $query)
    {
        $this->load($query);
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
     * @return array Entity | false
     */
    public function queryWith(QueryInterface $query)
    {
        $this->query($query);
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
        $this->load($qb);

        return $qb;
    }

    /**
     * Internal method to run load method on each field for the managed entity.
     *
     * Takes a QueryBuilder instance as input
     *
     * @param QueryBuilder $query
     */
    protected function load(QueryBuilder $query)
    {
        $metadata = $this->getClassMetadata();
        foreach ($metadata->getFieldMappings() as $field) {
            $fieldtype = $this->getFieldManager()->get($field['fieldtype'], $field);
            $fieldtype->load($query, $metadata);
        }
    }

    /**
     * Internal method to run query method on each field for the managed entity.
     *
     * Takes a QueryInterface instance as input
     *
     * @param QueryInterface $query
     */
    protected function query(QueryInterface $query)
    {
        $metadata = $this->getClassMetadata();

        foreach ($metadata->getFieldMappings() as $field) {
            $fieldtype = $this->getFieldManager()->get($field['fieldtype'], $field);
            $fieldtype->query($query, $metadata);
        }
    }

    /**
     * Internal method to run persist method on each field for the managed entity.
     *
     * Takes a QuerySet instance as input
     *
     * @param QuerySet $queries
     * @param Entity   $entity
     * @param array    $exclusions
     */
    protected function persist(QuerySet $queries, $entity, $exclusions = [])
    {
        $metadata = $this->getClassMetadata();

        foreach ($metadata->getFieldMappings() as $field) {
            if (in_array($field['fieldname'], $exclusions)) {
                continue;
            }

            $field = $this->getFieldManager()->get($field['fieldtype'], $field);
            $field->persist($queries, $entity);
        }
    }

    /**
     * Deletes a single object.
     *
     * @param object $entity The entity to delete.
     *
     * @return bool
     */
    public function delete($entity)
    {
        $event = new StorageEvent($entity);
        $this->event()->dispatch(StorageEvents::PRE_DELETE, $event);
        $qb = $this->em->createQueryBuilder()
            ->delete($this->getTableName())
            ->where('id = :id')
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
     * @param bool   $silent Suppress events
     *
     * @return bool
     */
    public function save($entity, $silent = null)
    {
        try {
            if ($existing = $entity->getId()) {
                $creating = false;
            } else {
                $creating = true;
            }
        } catch (\Exception $e) {
            $creating = $existing = false;
        }

        if ($silent === null) {
            $event = new StorageEvent($entity, ['create' => $creating]);
            $this->event()->dispatch(StorageEvents::PRE_SAVE, $event);
        }

        if ($existing) {
            $response = $this->update($entity);
        } else {
            $response = $this->insert($entity);
        }

        if ($silent === null) {
            $this->event()->dispatch(StorageEvents::POST_SAVE, $event);
        }

        return $response;
    }

    /**
     * Saves a new object into the database.
     *
     * @param object $entity The entity to insert.
     *
     * @return bool
     */
    public function insert($entity)
    {
        $querySet = new QuerySet();
        $qb = $this->em->createQueryBuilder();
        $qb->insert($this->getTableName());
        $querySet->append($qb);
        $this->persist($querySet, $entity, ['id']);

        $result = $querySet->execute();

        // Try and set the entity id using the response from the insert
        try {
            $entity->setId($querySet->getInsertId());
        } catch (\Exception $e) {
        }

        return $result;
    }

    /**
     * Updates an object into the database.
     *
     * @param object  $entity     The entity to update.
     * @param strin[] $exclusions Ignore updates to these fields
     *
     * @return bool
     */
    public function update($entity, $exclusions = [])
    {
        $querySet = new QuerySet();
        $qb = $this->em->createQueryBuilder();
        $qb->update($this->getTableName())
            ->where('id = :id')
            ->setParameter('id', $entity->getId());
        $querySet->append($qb);
        $this->persist($querySet, $entity, $exclusions);

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
        $entity = $this->getEntityBuilder()->getEntity();

        $preEventArgs = new HydrationEvent($data, ['entity' => $entity, 'repository' => $this]);
        $this->event()->dispatch(StorageEvents::PRE_HYDRATE, $preEventArgs);

        $this->getEntityBuilder()->createFromDatabaseValues($data, $entity);

        $postEventArgs = new HydrationEvent($entity, ['data' => $data, 'repository' => $this]);
        $this->event()->dispatch(StorageEvents::POST_HYDRATE, $postEventArgs);

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
     * Internal method to refresh (re-hydrate an entity) using
     * the field setters.
     *
     * @param  $entity
     */
    protected function refresh($entity)
    {
        $this->getEntityBuilder()->refresh($entity);
    }

    /**
     * Fetches FieldManager instance from the EntityManager.
     *
     * @return FieldManager
     */
    public function getFieldManager()
    {
        return $this->em->getFieldManager();
    }

    /**
     * @return Builder $builder
     */
    public function getEntityBuilder()
    {
        $builder = $this->em->getEntityBuilder();
        $builder->setClass($this->getEntityName());
        $builder->setClassMetadata($this->getClassMetadata());

        return $builder;
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
     * Getter for class metadata.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Shortcut method to fetch the Event Dispatcher.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function event()
    {
        return $this->getEntityManager()->getEventManager();
    }
}
