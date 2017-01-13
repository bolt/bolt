<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\EntityProxy;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LazyCollection Class - Allows a set of references to Entities to be loaded on demand
 *
 * @package Bolt\Storage\Collection
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LazyCollection extends ArrayCollection
{
    /**
     * Allows adding an entity proxy class
     *
     * @param EntityProxy $element
     * @return bool
     */
    public function add($element)
    {
        return parent::add($element);
    }

    /**
     *  Force loads the proxy objects and returns the real objects
     */
    public function serialize()
    {
        $output = [];
        foreach ($this as $element) {
            $output[] = $element->getProxy();
        }

        return $output;
    }
}
