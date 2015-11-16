<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\EntityManager;

/**
 * Collection Manager class
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CollectionManager
{
    public $collections;
    public $em;

    /**
     * @param $entity
     * @param $handler
     */
    public function setHandler($entity, $handler)
    {
        $this->collections[$entity] = $handler;
    }

    /**
     * Set an instance of EntityManager
     *
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em = null)
    {
        $this->em = $em;
    }

    public function create($class)
    {
        if (isset($this->collections[$class])) {
            return call_user_func($this->collections[$class]);
        }
    }
}
