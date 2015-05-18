<?php

namespace Bolt\Storage;

use Bolt\Mapping\ClassMetadata;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * This class prepares an entity instance ready to be persisted to the 
 * database. It consults handlers first before falling back to native doctrine
 * types.
 */
class Persister
{
    
    protected $metadata;
    
    public function __construct(ClassMetadata $metadata)
    {
        $this->metadata = $metadata; 
    }
    
    /**
     *  @param array source data
     * 
     *  @return Object Entity
     */
    public function persist($qb, $entity)
    {
                
        foreach ($this->metadata->getFieldMappings() as $key=>$mapping) {
            
            // First step is to allow each Bolt field to transform the data.
            $field = new $mapping['fieldtype']($mapping);
            $field->persist($qb, $entity);
            
            if ($mapping['type'] !== 'null') {
                $valueMethod = 'serialize'.ucfirst($key);
                $value = $entity->$valueMethod();
                $meta = $this->metadata->getFieldMapping($key);
                $type = Type::getType($meta['type']);
                if (null !== $value) {
                    $value = $type->convertToDatabaseValue($value, $qb->getConnection()->getDatabasePlatform());          
                }
                $qb->setValue($key, ":".$key);
                $qb->set($key, ":".$key);
                $qb->setParameter($key, $value);
            }
            
        }
        
        return $entity;
                
    }
    
    

    
}
