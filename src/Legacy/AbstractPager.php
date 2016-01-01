<?php

namespace Bolt\Legacy;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Class Pager
 * -----------
 *  Legacy class to keep compatibility for a while.
 *  It is just for remap deprecated properties like showing_from -> showingFrom
 */
abstract class AbstractPager implements \ArrayAccess
{
    /**
     * @var \Bolt\Pager\PagerManager
     */
    public $manager;

    public function __get($name)
    {
        $prop = $this->getCamelPropName($name);

        return $this->$prop;
    }

    public function __set($name, $value)
    {
        $prop = $this->getCamelPropName($name);

        $this->$prop = $value;
    }

    public function __isset($name)
    {
        $prop = $this->getCamelPropName($name);

        return isset($this->$prop);
    }

    public function offsetExists($offset)
    {
        return $this->manager->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->manager->offsetGet($offset);
    }

    /*
     * For BC purposes we should able to address pagers by indexed way via manager.
     * _page_nav.twig still uses ``{% set pager_ct = pager[context.contenttype.slug] %}``
     */

    public function offsetSet($offset, $value)
    {
        $this->manager->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->manager->offsetUnset($offset);
    }

    protected function getCamelPropName($name)
    {
        $prop = $this->camelize($name);
        if (!property_exists($this, $prop)) {
            throw new NoSuchPropertyException();
        }

        return $prop;
    }

    protected function camelize($varname)
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $varname))));
    }
}
