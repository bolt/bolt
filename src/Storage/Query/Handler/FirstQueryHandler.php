<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;

/**
 *  Handler to modifiy query based on activation of 'first' modifier.
 *
 *  eg: 'pages/first/3'
 */
class FirstQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     * @param int                $limit
     *
     * @return QueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $contentQuery->setDirective('order', 'id');

        return call_user_func_array($contentQuery->getHandler('select'), [$contentQuery]);
    }
}
