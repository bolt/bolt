<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\EntityProxy;
use CallbackFilterIterator;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LazyCollection Class - Allows a set of references to Entities to be loaded on demand.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LazyCollection extends ArrayCollection
{
    /**
     *  Force loads the proxy objects and returns the real objects
     */
    public function serialize()
    {
        $output = [];
        foreach ($this as $element) {
            $proxy = $element->getProxy();
            $output[] = $proxy->getContenttype() . '/' . $proxy->getSlug();
        }

        return $output;
    }

    /**
     * This overrides the base getIterator method and on access swaps out the EntityProxy objects
     * for real entities.
     *
     * @return CallbackFilterIterator
     */
    public function getIterator()
    {
        $els = parent::getIterator();

        return new CallbackFilterIterator($els, function (&$current) {
                /** @var EntityProxy $current */
                $current = $current->getProxy();

                return true;
            }
        );
    }
}
