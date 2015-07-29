<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Handler to add a limit modifier to the query.
 */
class LimitHandler
{
    /**
     * @param QueryInterface $query
     * @param int            $limit
     */
    public function __invoke(QueryInterface $query, $limit)
    {
        $query->getQueryBuilder()->setMaxResults($limit);
    }
}
