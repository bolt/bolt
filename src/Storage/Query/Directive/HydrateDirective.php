<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Directive to add a limit modifier to the query.
 */
class HydrateDirective
{
    /**
     * @param QueryInterface $query
     * @param int            $limit
     */
    public function __invoke(QueryInterface $query, $limit)
    {
        // Not implemented yet
    }
}
