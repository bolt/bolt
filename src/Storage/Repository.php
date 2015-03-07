<?php
namespace Bolt\Storage;
use Doctrine\Common\Persistence\ObjectRepository;


/**
 * An abstract class that other repositories can inherit.
 */
abstract class Repository implements ObjectRepository
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
    public function __construct($em, $class, $namingStrategy = null)
    {
        $this->em         = $em;
        $this->entityName  = $class;
        if (null === $namingStrategy) {
            $this->setNamingStrategy(new NamingStrategy());
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
            ->select($alias)
            ->from($this->getTableName(), $alias, $indexBy);
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
        return $this->em->find($this->entityName, $id);
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
        return $this->findBy($criteria, $orderBy, 1);
    }

    
    /**
     * @return string
     */
    protected function getEntityName()
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
    protected function getEntityManager()
    {
        return $this->em;
    }
    
    
    /**
     * @return void
     */
    protected function setNamingStrategy($handler)
    {
        $this->namingStrategy = $handler;
    }
    
    
    

}
