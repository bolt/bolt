<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Handler to specify that a single object, rather than an array should be returned
 */
class ReturnSingleHandler
{
    /**
     * @param QueryInterface $query
     */
    public function __invoke(QueryInterface $query)
    {
        $query->getQueryBuilder()->setMaxResults(1);
        $query->setSingleFetchMode(true);
    }
}
