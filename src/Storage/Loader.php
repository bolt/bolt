<?php

namespace Bolt\Storage;

use Bolt\Mapping\ClassMetadata;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * This class works on an instance of QueryBuilder transforming the query to the  
 * needs of the field handlers. 
 */
class Loader
{
    
    
    public $handlers = array();
    
    /**
     *  @param array source data
     * 
     *  @return Object Entity
     */
    public function load(QueryBuilder $qb, ClassMetadata $metadata)
    {
        foreach ($metadata->getFieldMappings() as $field) {
            $fieldtype = new $field['fieldtype']($field);
            $fieldtype->load($qb, $metadata);
        }

        return $qb;
    }
    
    

    
}
