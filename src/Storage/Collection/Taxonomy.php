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

    protected $em;
    protected $config;

    /**
     * Taxonomy constructor.
     */
    public function __construct($elements, EntityManager $em, $config = [])
    {
        $this->em = $em;
        $this->config = $config;
        parent::__construct($elements);

    }
}