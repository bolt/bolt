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
    public $classMaps;
    public $em;

    public function __construct($collections)
    {
        $this->collections = $collections;
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
        if (is_callable($this->collections[$class])) {
            return call_user_func($this->collections[$class]);
        }
    }
}