<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;
use Bolt\Storage;

/**
 * .
 */
class LegacyRepository extends Repository
{
    
    protected $storageService;
    
    
    /**
     * Setter to provide an instantiated version of
     * Bolt\Storage
     *
     * @param Bolt\Storage $storage
     */
    public function setLegacyStorage(Storage $service)
    {
        $this->storageService = $service;
    }
    
    public function getStorage()
    {
        return $this->storageService;
    }
    
    /**
     * Proxy to fetch a generic QueryBuilder from the entitymanager
     *
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null, $indexBy = null)
    {
        return $this->em->createQueryBuilder($alias, $indexBy);
    }
    
    public function getContentObject($contenttype, $values = array())
    {
        return $this->getStorage()->getContentObject($contenttype, $values);
    }
    
    public function preFill($contenttypes = array())
    {
        return $this->getStorage()->prefill($contenttypes);
    }
    
    public function saveContent(Content $content, $comment = null)
    {
        return $this->getStorage()->saveContent($content, $comment);
    }
    
    public function deleteContent($contenttype, $id)
    {
        return $this->getStorage()->deleteContent($contenttype, $id);
    }
    
    public function updateSingleValue($contenttype, $id, $field, $value)
    {
        return $this->getStorage()->updateSingleValue($contenttype, $id, $field, $value);
    }
    
    public function getEmptyContent($contenttypeslug)
    {
        return $this->getStorage()->getEmptyContent($contenttypeslug);
    }

    public function searchContent($q, array $contenttypes = null, array $filters = null, $limit = 9999, $offset = 0)
    {
        return $this->getStorage()->searchContent($q, $contenttypes, $filters, $limit, $offset);
    }
    
    public function searchAllContentTypes(array $parameters = array(), &$pager = array())
    {
        return $this->getStorage()->searchAllContentTypes($parameters, $pager);
    }

    public function searchContentType($contenttypename, array $parameters = array(), &$pager = array())
    {
        return $this->getStorage()->searchContentType($contenttypename, $parameters, $pager);
    }
    
    public function getContentByTaxonomy($taxonomyslug, $name, $parameters = "")
    {
        return $this->getStorage()->getContentByTaxonomy($taxonomyslug, $name, $parameters);
    }

    public function publishTimedRecords($contenttype)
    {
        return $this->getStorage()->publishTimedRecords($contenttype);
    }
    
    public function depublishExpiredRecords($contenttype)
    {
        return $this->getStorage()->depublishExpiredRecords($contenttype);
    }

    public function getContent($textquery, $parameters = '', &$pager = array(), $whereparameters = array())
    {
        return $this->getStorage()->getContent($textquery, $parameters, $pager, $whereparameters);
    }
    
    public function getSortOrder($name = '-datepublish')
    {
        return $this->getStorage()->getSortOrder($name);
    }
    
    public function getContentType($contenttypeslug)
    {
        return $this->getStorage()->getContentType($contenttypeslug);
    }
    
    public function getTaxonomyType($taxonomyslug)
    {
        return $this->getStorage()->getTaxonomyType($taxonomyslug);
    }
    
    public function getContentTypes()
    {
        return $this->getStorage()->getContentTypes();
    }
    
    public function getContentTypeFields($contenttypeslug)
    {
        return $this->getStorage()->getContentTypeFields($contenttypeslug);
    }
    
    public function getContentTypeFieldType($contenttypeslug, $fieldname)
    {
        return $this->getStorage()->getContentTypeFieldType($contenttypeslug, $fieldname);
    }
    
    public function getContentTypeGrouping($contenttypeslug)
    {
        return $this->getStorage()->getContentTypeGrouping($contenttypeslug);
    }
    
    public function getContentTypeTaxonomy($contenttypeslug)
    {
        return $this->getStorage()->getContentTypeTaxonomy($contenttypeslug);
    }
    
    public function getLatestId($contenttypeslug)
    {
        return $this->getStorage()->getLatestId($contenttypeslug);
    }
    
    
    public function getUri($title, $id = 0, $contenttypeslug = "", $fulluri = true, $allowempty = true, $slugfield = 'slug')
    {
        return $this->getStorage()->getUri($title, $id, $contenttypeslug, $fulluri, $allowempty, $slugfield);
    }


    

    

}
