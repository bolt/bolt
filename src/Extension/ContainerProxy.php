<?php

namespace Bolt\Extension;

use Pimple as Container;

/**
 * A Container proxy that prevents non-whitelisted services from being invoked.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ContainerProxy extends Container
{
    /** @var Container */
    private $container;
    /** @var string[] */
    private $serviceWhiteList = [];

    /**
     * Constructor.
     *
     * @param Container $container        The real container.
     * @param string[]  $serviceWhiteList A list of services that can be invoked.
     */
    public function __construct(Container $container, array $serviceWhiteList = [])
    {
        parent::__construct([]);
        $this->container = $container;
        $this->serviceWhiteList = $serviceWhiteList;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($id, $value)
    {
        $this->container->offsetSet($id, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($id)
    {
        if (!in_array($id, $this->serviceWhiteList)) {
            throw new \LogicException(sprintf('You cannot retrieve services until the app has been booted. Attempting to access %s', $id));
        }

        return $this->container->offsetGet($id);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($id)
    {
        return $this->container->offsetExists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($id)
    {
        $this->container->offsetUnset($id);
    }

    /**
     * {@inheritdoc}
     */
    public function raw($id)
    {
        return $this->container->raw($id);
    }

    /**
     * {@inheritdoc}
     */
    public function extend($id, $callable)
    {
        return $this->container->extend($id, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return $this->container->keys();
    }
}
