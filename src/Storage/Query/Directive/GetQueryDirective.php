<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Directive that allows running of a callback on query.
 */
class GetQueryDirective
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
