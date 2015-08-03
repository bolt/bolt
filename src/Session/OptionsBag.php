<?php

namespace Bolt\Session;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * This is a Bag. It does things.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class OptionsBag extends ParameterBag implements \ArrayAccess
{
    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
