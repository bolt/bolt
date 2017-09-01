<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryResultset;

/**
 *  Handler to modify query based on activation of 'first' modifier.
 *
 *  eg: 'pages/first/3'
 */
class FirstQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return QueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $contentQuery->setDirective('order', 'id');

        return call_user_func($contentQuery->getHandler('select'), $contentQuery);
    }
}
