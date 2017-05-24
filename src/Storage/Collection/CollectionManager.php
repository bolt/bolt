<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection Manager class.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CollectionManager
{
    /** @var callable[] */
    public $collections;
    /** @var EntityManager */
    public $em;

    /**
     * @param string   $entity
     * @param callable $handler
     */
    public function setHandler($entity, $handler)
    {
        $this->collections[$entity] = $handler;
    }

    /**
     * Set an instance of EntityManager.
     *
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em = null)
    {
        $this->em = $em;
    }

    /**
     * @param string $class
     *
     * @throws \InvalidArgumentException
     *
     * @return ArrayCollection
     */
    public function create($class)
    {
        if (!isset($this->collections[$class])) {
            throw new \InvalidArgumentException(sprintf('No collection handler exists for %s', $class));
        }

        return call_user_func($this->collections[$class]);
    }
}
