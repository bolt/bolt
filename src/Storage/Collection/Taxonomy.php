<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Taxonomy Entities
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Taxonomy extends ArrayCollection
{

    protected $metadata;

    /**
     * Taxonomy constructor.
     * @param array $metadata
     */
    public function __construct($metadata)
    {
        $this->$metadata = $metadata;

    }
}