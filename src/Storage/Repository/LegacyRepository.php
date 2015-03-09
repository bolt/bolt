<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;


/**
 * .
 */
class LegacyRepository extends Repository
{
    
    /**
     * Proxy to fetch a generic QueryBuilder from the entitymanager
     *
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->em->createQueryBuilder();
    }
    
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
