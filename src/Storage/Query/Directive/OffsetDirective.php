<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\Query\SelectQuery;

/**
 *  Directive to add a limit modifier to the query.
 */
class OffsetDirective
{
    /**
     * @param SelectQuery $query
     * @param $page
     */
    public function __invoke(SelectQuery $query, $page)
    {
        $limit = $query->getParameter('limit');
        $query->getQueryBuilder()->setFirstResult(($page - 1) * $limit);
    }
}
