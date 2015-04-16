<?php

namespace Bolt\Storage;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Query\QueryBuilder;
use Bolt\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

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
    public function hydrate(array $source, QueryBuilder $qb)
    {
        $classname = $this->handler;
        $entity = new $classname;
        foreach ($source as $key => $val) {
            $meta = $this->metadata->getFieldMapping($key);
            $type = Type::getType($meta['type']);
            $value = $type->convertToPHPValue($val, $qb->getConnection()->getDatabasePlatform());
            $entity->$key = $value;
        }
        return $entity;
    }

    
}
