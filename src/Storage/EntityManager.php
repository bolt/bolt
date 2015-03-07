<?php
namespace Bolt\Storage;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Manages all loaded entities across application, provides access to Repository Classes.
 */
class EntityManager
{
    
    protected $conn;
    protected $eventManager;
    protected $repositories = array();
    
    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given EventManager.
     *
     * @param \Doctrine\DBAL\Connection     $conn
     * @param \Doctrine\Common\EventManager $eventManager
     */
    public function __construct(Connection $conn, EventManager $eventManager)
    {
        $this->conn              = $conn;
        $this->eventManager      = $eventManager;

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
        if (array_key_exists($className, $this->repositories)) {
            $repoClass = $this->repositories[$className];
            return new $repoClass($this, $className);
        } 
        
        return new Repository($this, $className);
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
    
    
    
    /******* Deprecated functions ******/
    
    public function getContentObject($contenttype, $values = array())
    {
        
    }
    
    public function preFill($contenttypes = array())
    {
        
    }
    
    public function saveContent(Content $content, $comment = null)
    {
        
    }
    
    public function deleteContent($contenttype, $id)
    {
        
    }
    
    public function updateSingleValue($contenttype, $id, $field, $value)
    {
        
    }
    
    public function getEmptyContent($contenttypeslug)
    {
        
    }

    public function searchContent($q, array $contenttypes = null, array $filters = null, $limit = 9999, $offset = 0)
    {
        
    }
    
    public function searchAllContentTypes(array $parameters = array(), &$pager = array())
    {
        
    }

    public function searchContentType($contenttypename, array $parameters = array(), &$pager = array())
    {
        
    }
    
    public function getContentByTaxonomy($taxonomyslug, $name, $parameters = "")
    {
        
    }

    public function publishTimedRecords($contenttype)
    {
        
    }
    
    public function depublishExpiredRecords($contenttype)
    {
        
    }

    public function getContent($textquery, $parameters = '', &$pager = array(), $whereparameters = array())
    {
        
    }
    
    public function getSortOrder($name = '-datepublish')
    {
        
    }
    
    public function getContentType($contenttypeslug)
    {
        
    }
    
    public function getTaxonomyType($taxonomyslug)
    {
        
    }
    
    public function getContentTypes()
    {
        
    }
    
    public function getContentTypeFields($contenttypeslug)
    {
        
    }
    
    public function getContentTypeFieldType($contenttypeslug, $fieldname)
    {
        
    }
    
    public function getContentTypeGrouping($contenttypeslug)
    {
        
    }
    
    public function getContentTypeTaxonomy($contenttypeslug)
    {
        
    }
    
    public function getLatestId($contenttypeslug)
    {
        
    }
    
    
    public function getUri($title, $id = 0, $contenttypeslug = "", $fulluri = true, $allowempty = true, $slugfield = 'slug')
    {
        
    }












}
