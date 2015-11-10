<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Mapping\MetadataDriver;
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
     * @param MetadataDriver $metadata
     */
    public function __construct(MetadataDriver $metadata)
    {
        $this->$metadata = $metadata;

    }
}