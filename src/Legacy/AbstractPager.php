<?php

namespace Bolt\Pager;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Class Pager
 * -----------
 *  Legacy class to keep compatibility for a while.
 *  It is just for remap deprecated properties like showing_from -> showingFrom
 */
abstract class AbstractPager
{
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

    protected function getCamelPropName($name)
    {
        $prop = $this->camelize($name);
        if (!property_exists(__CLASS__, $prop)) {
            throw new NoSuchPropertyException();
        }

        return $prop;
    }

    protected function camelize($varname)
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $varname))));
    }
}
