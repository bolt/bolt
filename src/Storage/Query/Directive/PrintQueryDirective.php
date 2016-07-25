<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Directive a raw output of the generated query.
 */
class PrintQueryDirective
{
    /**
     * @param QueryInterface $query
     */
    public function __invoke(QueryInterface $query)
    {
        echo $query;
    }
}
