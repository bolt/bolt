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
class PathsProxy implements \ArrayAccess
{
    /** @var ResourceManager */
    protected $resources;

    /**
     * Constructor.
     *
     * @param ResourceManager $resources
     */
    public function __construct(ResourceManager $resources)
    {
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        try {
            return $this->resources->getRequest($offset);
        } catch (\InvalidArgumentException $e) {
        }

        try {
            return $this->resources->getUrl($offset);
        } catch (\InvalidArgumentException $e) {
        }

        try {
            return $this->resources->getPath($offset);
        } catch (\InvalidArgumentException $e) {
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Use Bolt\Configuration\ResourceManager::setUrl() or setPath() instead.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('You cannot remove urls or paths.');
    }
}
