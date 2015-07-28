<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Handler that allows running of a callback on query.
 */
class GetQueryHandler
{
    /**
     * @param QueryInterface $query
     * @param callable       $callback
     */
    public function __invoke(QueryInterface $query, callable $callback)
    {
        $callback($query);
    }
}
