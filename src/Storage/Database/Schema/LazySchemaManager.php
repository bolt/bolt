<?php

namespace Bolt\Storage\Database\Schema;

class LazySchemaManager implements SchemaManagerInterface
{
    /** @var \Closure */
    private $factory;
    /** @var Manager */
    private $manager;

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
     * @return Manager
     */
    public function getManager()
    {
        if (!$this->manager) {
            $this->manager = call_user_func($this->factory);
            if (!$this->manager instanceof Manager) {
                throw new \LogicException('Factory supplied to LazySchemaManager must return implementation of Manager.');
            }
        }

        return $this->manager;
    }

    /**
     * @inheritDoc
     */
    public function isCheckRequired()
    {
        return $this->getManager()->isCheckRequired();
    }

    /**
     * {@inheritdoc}
     */
    public function isUpdateRequired()
    {
        return $this->getManager()->isUpdateRequired();
    }
}
