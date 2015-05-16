<?php

namespace Bolt\Storage;

use Bolt\Storage\EntityManager;

/**
 *  This class is used by lazily loaded entities. It stores a reference to an entity but only
 *  fetches it on demand.
 */
class EntityProxy
{
    
    public $entity;
    public $reference;
    
    
    public function __construct($entity, $reference)
    {
        $this->entity = $entity;
        $this->reference = $reference;
    }
    
    public function load(EntityManager $em)
    {
        return $em->find($this->entity, $this->reference);
    }
    

    
}
