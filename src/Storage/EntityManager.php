<?php
namespace Bolt\Storage;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;

/**
 * Manages all loaded entities across application, provides access to Repository Classes.
 */
class EntityManager
{
    
    protected $conn;
    protected $eventManager;
    protected $mapping;
    protected $repositories = array();
    protected $aliases = array();
    
    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given EventManager.
     *
     * @param \Doctrine\DBAL\Connection     $conn
     * @param EventDispatcherInterface      $eventManager
     */
    public function __construct(Connection $conn, EventDispatcherInterface $eventManager, MappingDriver $mapping)
    {
        $this->conn         = $conn;
        $this->eventManager = $eventManager;
        $this->mapping      = $mapping;
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
     * undocumented 
     *
     * @return LagacyRepository
     */
    public function legacy()
    {
        return new Repository\LegacyRepository($this, 'Legacy');
    }
    
    
    
    /******* Deprecated functions ******/
    
    public function getContentObject($contenttype, $values = array())
    {
        return $this->legacy()->getContentObject($contenttype, $values);
    }
    
    public function preFill($contenttypes = array())
    {
        return $this->legacy()->prefill($contenttypes);
    }
    
    public function saveContent(Content $content, $comment = null)
    {
        return $this->legacy()->saveContent($content, $comment);
    }
    
    public function deleteContent($contenttype, $id)
    {
        return $this->legacy()->deleteContent($contenttype, $id);        
    }
    
    public function updateSingleValue($contenttype, $id, $field, $value)
    {
        return $this->legacy()->updateSingleValue($contenttype, $id, $field, $value);                
    }
    
    public function getEmptyContent($contenttypeslug)
    {
        return $this->legacy()->getEmptyContent($contenttypeslug);
    }

    public function searchContent($q, array $contenttypes = null, array $filters = null, $limit = 9999, $offset = 0)
    {
        return $this->legacy()->searchContent($q, $contenttypes, $filters, $limit, $offset);        
    }
    
    public function searchAllContentTypes(array $parameters = array(), &$pager = array())
    {
        return $this->legacy()->searchAllContentTypes($parameters, $pager);
    }

    public function searchContentType($contenttypename, array $parameters = array(), &$pager = array())
    {
        return $this->legacy()->searchContentType($contenttypename, $parameters, $pager);        
    }
    
    public function getContentByTaxonomy($taxonomyslug, $name, $parameters = "")
    {
        return $this->legacy()->getContentByTaxonomy($taxonomyslug, $name, $parameters);                
    }

    public function publishTimedRecords($contenttype)
    {
        return $this->legacy()->publishTimedRecords($contenttype);
    }
    
    public function depublishExpiredRecords($contenttype)
    {
        return $this->legacy()->depublishExpiredRecords($contenttype);        
    }

    public function getContent($textquery, $parameters = '', &$pager = array(), $whereparameters = array())
    {
        return $this->legacy()->getContent($textquery, $parameters, $pager, $whereparameters);                
    }
    
    public function getSortOrder($name = '-datepublish')
    {
        return $this->legacy()->getSortOrder($name);
    }
    
    public function getContentType($contenttypeslug)
    {
        return $this->legacy()->getContentType($contenttypeslug);        
    }
    
    public function getTaxonomyType($taxonomyslug)
    {
        return $this->legacy()->getTaxonomyType($taxonomyslug);                
    }
    
    public function getContentTypes()
    {
        return $this->legacy()->getContentTypes();                        
    }
    
    public function getContentTypeFields($contenttypeslug)
    {
        return $this->legacy()->getContentTypeFields($contenttypeslug);                                
    }
    
    public function getContentTypeFieldType($contenttypeslug, $fieldname)
    {
        return $this->legacy()->getContentTypeFieldType($contenttypeslug, $fieldname);                                        
    }
    
    public function getContentTypeGrouping($contenttypeslug)
    {
        return $this->legacy()->getContentTypeGrouping($contenttypeslug);                                                
    }
    
    public function getContentTypeTaxonomy($contenttypeslug)
    {
        return $this->legacy()->getContentTypeTaxonomy($contenttypeslug);                                                        
    }
    
    public function getLatestId($contenttypeslug)
    {
        return $this->legacy()->getLatestId($contenttypeslug);                                                                
    }
    
    
    public function getUri($title, $id = 0, $contenttypeslug = "", $fulluri = true, $allowempty = true, $slugfield = 'slug')
    {
        return $this->legacy()->getUri($title, $id, $contenttypeslug, $fulluri, $allowempty, $slugfield);                                                                        
    }


}
