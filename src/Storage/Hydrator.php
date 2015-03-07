<?php

namespace Bolt\Storage;

use Doctrine\Common\EventSubscriber;

/**
 * Maps raw sql query data to Bolt Entities
 */
class Hydrator
{
    
    /**
     *  @param array source data
     * 
     *  @return Object Entity
     */
    public function hydrate(array $source, $entity)
    {
        return new $entity($source);
    }

    
}
