<?php

namespace Bolt\Storage;

/**
 * Lazy-loading EntityManager.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LazyEntityManager implements EntityManagerInterface
{
    /** @var callable $factory */
    private $factory;
    /** @var EntityManager $urlGenerator */
    private $em;

    /**
     * Constructor.
     *
     * @param callable $factory Should return EntityManager when invoked
     */
    public function __construct(callable $factory)
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

    /**
     * @inheritDoc
     */
    public function getRepository($className)
    {
        return $this->getEntityManager()->getRepository($className);
    }
}
