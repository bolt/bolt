<?php

namespace Bolt\Storage;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * This class prepares an entity instance ready to be persisted to the 
 * database. It consults handlers first before falling back to native doctrine
 * types.
 */
class Persister
{
    
    
    public $handlers = array();
    
    /**
     *  @param array source data
     * 
     *  @return Object Entity
     */
    public function persist($qb, $entity, $metadata)
    {
        foreach ($entity->toArray() as $key=>$value) {
            $meta = $metadata->getFieldMapping($key);
            $type = Type::getType($meta['type']);
            $value = $type->convertToDatabaseValue($value, $qb->getConnection()->getDatabasePlatform());          
            $qb->setValue($key, ":".$key);
            $qb->setParameter($key, $value);
        }
        
        return $qb;
    }
    
    

    
}
