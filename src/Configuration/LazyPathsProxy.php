<?php

namespace Bolt\Configuration;

/**
 * Bolt\Configuration\ResourceManager::getPaths() is proxied here, which used to return a simple array.
 *
 * This allows us to still use getPath, getUrl, and getRequest methods which have custom logic in them to maintain BC.
 *
 * @deprecated since 3.0, to be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class LazyPathsProxy extends PathsProxy
{
    /** @var callable */
    private $factory;

    /**
     * Constructor.
     *
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->resources) {
            $this->resources = call_user_func($this->factory);
            if (!$this->resources instanceof ResourceManager) {
                throw new \LogicException(
                    sprintf(
                        'Factory given to %s must return an implementation of %s',
                        static::class,
                        ResourceManager::class
                    )
                );
            }
        }

        return parent::offsetGet($offset);
    }
}
