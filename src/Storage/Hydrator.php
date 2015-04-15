<?php

namespace Bolt\Storage;

use Doctrine\Common\EventSubscriber;
use Bolt\Mapping\ClassMetadata;

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
    public function hydrate(array $source)
    {
        $classname = $this->handler;
        return new $classname($source);
    }

    
}
