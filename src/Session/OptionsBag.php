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
     * Returns a parameter by name. If the parameter hasn't been set it is pulled from php.ini
     *
     * @param string  $path    The key
     * @param mixed   $default
     * @param boolean $deep
     *
     * @return mixed
     */
    public function get($path, $default = null, $deep = false)
    {
        return parent::get($path, null, false) ?: ini_get('session.' . $path);
    }

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
