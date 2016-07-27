<?php

namespace Bolt\Storage;

/**
 * Lazy-loading EntityManager.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LazyEntityManager
{
    /** @var \Closure $factory */
    private $factory;
    /** @var EntityManager $urlGenerator */
    private $em;

    /**
     * Constructor.
     *
     * @param \Closure $factory Should return EntityManager when invoked
     */
    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->em) {
            $this->em = call_user_func($this->factory);
            if (!$this->em instanceof EntityManager) {
                throw new \LogicException('Factory supplied to LazyEntityManager must return implementation of EntityManager.');
            }
        }

        return $this->em;
    }
}
