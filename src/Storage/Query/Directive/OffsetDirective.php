<?php

namespace Bolt\Storage\Query\Directive;

use Bolt\Storage\Query\SelectQuery;

/**
 *  Directive to add a limit modifier to the query.
 */
class OffsetDirective
{
    /**
     * @param SelectQuery $query
     * @param $page
     * @param $otherDirectives
     */
    public function __invoke(SelectQuery $query, $page, $otherDirectives)
    {
        $limit = $otherDirectives['limit'] ? $otherDirectives['limit'] : 0;
        $query->getQueryBuilder()->setFirstResult(($page - 1) * $limit);
    }
}
