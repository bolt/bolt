<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityProxy;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LazyCollection Class - Allows a set of references to Entities to be loaded on demand.
 *
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LazyCollection extends ArrayCollection
{
    /**
     * Allows adding an entity proxy class.
     *
     * @param EntityProxy $element
     *
     * @return bool
     */
    public function add($element)
    {
        return parent::add($element);
    }

    /**
     * Force loads the proxy objects and returns the required data for Twig.
     */
    public function getProxy()
    {
        $output = [];
        foreach ($this as $element) {
            /** @var Content $proxy */
            $proxy = $element->getProxy();
            $output[] = $proxy->getContenttype() . '/' . $proxy->getSlug();
        }

        return $output;
    }
}
