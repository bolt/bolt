<?php

namespace Bolt\Storage;

use Doctrine\Common\EventSubscriber;

/**
 * Maps raw sql query data to Bolt Entities
 */
class Hydrator
{
    
    protected $handler;
    
    /**
     * Hydrator class, converts database values into PHP OO Structure
     * Handlers can be registered to replace default strategy.
     *
     * @param $classHandler A PHP Class that will be used to store Hydrated data.
     * 
     * @return void
     **/
    public function __construct($classHandler)
    {
        if (!class_exists($classHandler)) {
            throw new \InvalidArgumentException("Value passed must be a valid class name", 1);
        }
        $this->handler = $classHandler;   
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
