<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\EntityProxy;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LazyCollection Class - Allows a set of references to Entities to be loaded on demand
 *
 * @package Bolt\Storage\Collection
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LazyCollection extends AbstractLazyCollection
{
    /**
     * Allows adding an entity proxy class
     *
     * @param EntityProxy $element
     *
     * @return
     */
    public function add($element)
    {
        $this->initialize();

        return $this->collection->add($element);
    }

    protected function doInitialize()
    {
        $this->collection = new ArrayCollection();
    }
}
