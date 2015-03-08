<?php
namespace Bolt\Storage;

use Doctrine\Common\Persistence\ObjectRepository;
use Bolt\Events\HydrationEventArgs;
use Bolt\Events\StorageEvents;


/**
 * An abstract class that other repositories can inherit.
 */
class Repository implements ObjectRepository
{
    
    public $em;
    public $entityName;
    public $namingStrategy;
    
    /**
     * Initializes a new <tt>Repository</tt>.
     *
     * @param EntityManager         $em    The EntityManager to use.
     * @param Mapping\ClassMetadata $class The class descriptor.
     */
    public function __construct($em, $class, $namingStrategy = null, $hydrator = null)
    {
        $this->em         = $em;
        $this->entityName  = $class;
        if (null === $namingStrategy) {
            $this->setNamingStrategy(new NamingStrategy());
        }
        if (null === $hydrator) {
            $this->setHydrator(new Hydrator());
        }
    }
    
    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null, $indexBy = null)
    {
        if (null === $alias) {
            $alias = $this->getAlias();
        }
        return $this->em->createQueryBuilder()
            ->select($alias.".*")
            ->from($this->getTableName(), $alias);
    }

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     *
     * @return object The object.
     */
    public function find($id)
    {
        $qb = $this->createQueryBuilder();
        
        return $this->hydrate($qb->execute()->fetch());
    }

    /**
     * Finds all objects in the repository.
     *
     * @return array The objects.
     */
    public function findAll()
    {
        return $this->findBy(array());
    }

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     *
     * @throws \UnexpectedValueException
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->findWithCriteria($criteria, $orderBy, $limit, $offset);
        
        return $this->hydrateAll($qb->execute()->fetchAll());
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria The criteria.
     *
     * @return object The object.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $qb = $this->findWithCriteria($criteria, $orderBy);
        
        return $this->hydrate($qb->execute()->fetch());
    }
    
    /**
     * Internal method to build a basic select, returns QB object.
     * 
     * @return QueryBuilder.
     */
    protected function findWithCriteria(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder();
        foreach ($criteria as $col=>$val) {
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
     * Deletes a single object.
     *
     * @param object $$object The entity to delete.
     *
     * @return bool.
     */
    public function delete($entity)
    {
        $qb = $this->em->createQueryBuilder()
            ->delete($this->getTableName())
            ->where("id = :id")
            ->setParameter('id', $entity->getId());
        
        
        return $qb->execute();
    }
    
    /**
     * Saves a single object.
     *
     * @param object $$object The entity to delete.
     *
     * @return bool.
     */
    public function save($entity)
    {
        $qb = $this->em->createQueryBuilder();
        
        try {
            $existing = $entity->getId();
        } catch (Exception $e) {
            $existing = false;
        }
        
        if ($existing) {
            return $this->update($entity);
        } else {
            return $this->insert($entity);
        }
                
    }
    
    /**
     * Saves a new object into the database.
     *
     * @param object $$object The entity to insert.
     *
     * @return bool.
     */
    public function insert($entity)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->insert($this->getTableName());
        foreach ($entity->toArray() as $key=>$value) {
            $qb->setValue($key, ":".$key);
            $qb->setParameter($key, $value);
        }
        
        return $qb->execute();
    }
    
    /**
     * Updates an object into the database.
     *
     * @param object $$object The entity to update.
     *
     * @return bool.
     */
    public function update($entity)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update($this->getTableName());        
        foreach ($entity->toArray() as $key=>$value) {
            $qb->set($key, ":".$key);
            $qb->setParameter($key, $value);
        }
        
        return $qb->execute();
    }
    
    
    
    /**
     * Internal method to hydrate an Entity Object from fetched data.
     * 
     * @return mixed.
     */
    protected function hydrate(array $data)
    {
        $args = new HydrationEventArgs($data, $this->getEntityName(), $this);
        $this->getEntityManager()->getEventManager()->dispatchEvent(StorageEvents::PRE_HYDRATE, $args);
        $entity = $this->hydrator->hydrate($data, $this->getEntityName());
        $args = new HydrationEventArgs($data, $entity, $this);
        $this->getEntityManager()->getEventManager()->dispatchEvent(StorageEvents::POST_HYDRATE, $args);
        
        return $entity;
    }
    
    /**
     * Internal method to hydrate an array of Entity Objects from fetched data.
     * 
     * @return mixed.
     */
    protected function hydrateAll(array $data)
    {
        $rows = array();
        foreach ($data as $row) {
           $rows[] = $this->hydrate($row); 
        }
        
        return $rows;
    }
    
    /**
     * @return void
     */
    public function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
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
     * 
     * @return string
     */
    public function getTableName()
    {
        return $this->namingStrategy->classToTableName($this->getEntityName());
    }
    
    /**
     * 
     * @return string
     */
    public function getAlias()
    {
        return $this->namingStrategy->classToAlias($this->getEntityName());
    }


    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
    
    
    /**
     * @return void
     */
    public function setNamingStrategy($handler)
    {
        $this->namingStrategy = $handler;
    }
    
    
    

}
