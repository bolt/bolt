<?php

namespace Bolt\Storage;

use Bolt\Mapping\ClassMetadata;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Maps raw sql query data to Bolt Entities
 */
class Hydrator
{
    protected $handler;
    
    protected $metadata;
    
    /**
     * Hydrator class, converts database values into PHP OO Structure
     * Handlers can be registered to replace default strategy.
     *
     * @param $classHandler A PHP Class that will be used to store Hydrated data.
     *
     * @return void
     **/
    public function __construct(ClassMetadata $metadata)
    {
        $classHandler = $metadata->getName();
        if (!class_exists($classHandler)) {
            throw new \InvalidArgumentException("Value supplied $classHandler is not a valid class name", 1);
        }
        $this->handler = $classHandler;
        $this->metadata = $metadata;
    }
    
    /**
     *  @param array source data
     *
     *  @return Object Entity
     */
    public function hydrate(array $source, QueryBuilder $qb, EntityManager $em = null)
    {
        $classname = $this->handler;
        $entity = new $classname;
        $entity->setContenttype($this->metadata->getBoltName());
                
        foreach ($this->metadata->getFieldMappings() as $key => $mapping) {
            
            // First step is to allow each Bolt field to transform the data.
            $field = new $mapping['fieldtype']($mapping);
            $field->hydrate($source, $entity, $em);
        }
        
        return $entity;
    }
}
