<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\QueryInterface;

/**
 *  Handler a raw output of the generated query.
 */
class PrintQueryHandler
{
    /**
     * @param QueryInterface $query
     */
    public function __invoke(QueryInterface $query)
    {
        echo $query;
    }
}
