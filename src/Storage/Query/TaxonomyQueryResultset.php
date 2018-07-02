<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\Collection\LazyCollection;
use Bolt\Storage\EntityManager;
use Bolt\Storage\EntityProxy;

/**
 * This class builds on the default QueryResultset to add
 * the ability to merge sets based on weighted scores.
 */
class TaxonomyQueryResultset extends QueryResultset
{
    protected $em;

    public function getCollection()
    {
        $collection = new LazyCollection();

        foreach ($this->results as $proxy) {
            $collection->add(new EntityProxy($proxy['contenttype'], $proxy['id'], $this->getEntityManager()));
        }

        return $collection;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
