<?php
namespace Bolt\Storage;

use Bolt\Storage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Manages all loaded entities across application, provides access to Repository Classes.
 */
class EntityManager
{
    
    protected $conn;
    protected $eventManager;
    protected $mapping;
    protected $log;
    protected $repositories = array();
    protected $aliases = array();
    protected $legacyStorage;
    
    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given EventManager.
     *
     * @param \Doctrine\DBAL\Connection     $conn
     * @param EventDispatcherInterface      $eventManager
     * @param MappingDriver                 $mapping
     * @param LoggerInterface               $logger
     */
    public function __construct(Connection $conn, EventDispatcherInterface $eventManager, MappingDriver $mapping, LoggerInterface $log = null)
    {
        $this->conn         = $conn;
        $this->eventManager = $eventManager;
        $this->mapping      = $mapping;
        if (null === $log) {
            $this->log = new NullLogger();
        } else {
            $this->log          = $log;
        }
    }
    
    /**
     * 
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->conn);
    }


    /**
     * Finds an object by its identifier.
     *
     * This is just a convenient shortcut for getRepository($className)->find($id).
     *
     * @param string $className The class name of the object to find.
     * @param mixed  $id        The identity of the object to find.
     *
     * @return object The found object.
     */
    public function find($className, $id)
    {
        $repo = $this->getRepository($className);
        
        return $repo->find($id);
    }

    /**
     *
     * The object will be entered into the database as a result of this operation.
     *
     *
     * @param object $object The instance to persist to storage.
     *
     * @return boolean
     */
    public function save($object)
    {
        $entityName = get_class($object);
        $repo = $this->getRepository($entityName);
        
        return $repo->save($object);
    }

    /**
     * Removes an object instance.
     *
     * Passed in object will be removed from the database as a result of this operation.
     *
     * @param object $object The object instance to remove.
     *
     * @return boolean
     */
    public function delete($object)
    {
        $entityName = get_class($object);
        $repo = $this->getRepository($entityName);
        
        return $repo->delete($object);
    }


    /**
     * Gets the repository for a class.
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($className)
    {
        if (array_key_exists($className, $this->aliases)) {
            $classMetadata = $this->getMapper()->loadMetadataForClass($this->aliases[$className]);
        } else {
            $classMetadata = $this->getMapper()->loadMetadataForClass($className);
        }
        
        if (array_key_exists($className, $this->repositories)) {
            $repoClass = $this->repositories[$className];
            return new $repoClass($this, $classMetadata);
        }
        
        foreach ($this->aliases as $alias=>$namespace) {
            $full = str_replace($alias, $namespace, $className);
            
            if (array_key_exists($full, $this->repositories)) {
                $classMetadata = $this->getMapper()->loadMetadataForClass($full);
                $repoClass = $this->repositories[$full];
                return new $repoClass($this, $classMetadata);
            }
            
        }
        
        return new Repository($this, $classMetadata);
    }
    
    /**
     * Sets a custom repository class for an entity.
     *
     * @param string $entityName
     * @param string $repositoryClass
     * 
     */
    public function setRepository($entityName, $repositoryClass)
    {
        $this->repositories[$entityName] = $repositoryClass;
    }
    
    
    
    /**
     * Gets the Event Manager.
     *
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }
    
    /**
     * Gets the Class Metadata Driver.
     *
     * @return ClassMetadata
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
     * 
     * @return void
     */
    public function addEntityAlias($alias, $namespace)
    {
        $this->aliases[$alias] = $namespace;
    }

    /**
     * Returns a proxy to the legacy storage service
     *
     * @return LegacyRepository
     */
    public function legacy()
    {        
        return $this->legacyStorage;
    }
    
    /**
     * Sets the LegacyRepository
     *
     * @param Storage $storage
     */
    public function setLegacyStorage(Storage $storage)
    {
        $this->legacyStorage = $storage;
    }
    
    /**
     * Getter for logger object
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->log;
    }
    
    
    
    /******* Deprecated functions ******/
    
    /**
     * Magic call method acts as a catchall proxy to the legacy repository
     *
     * @param string $method 
     * @param string $args 
     */
    public function __call($method, $args)
    {
        $this->getLogger()->warning("[DEPRECATED] Accessing ['storage']->$method is no longer supported");
        return call_user_func_array(array($this->legacy(), $method), $args);
    }
    
}
