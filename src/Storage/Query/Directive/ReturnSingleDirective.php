<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Directive to specify that a single object, rather than an array should be returned.
 */
class ReturnSingleDirective
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
