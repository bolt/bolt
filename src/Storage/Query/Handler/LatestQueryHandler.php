<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;

/**
 *  Handler to modifiy query based on activation of 'latest' modifier.
 *
 *  eg: 'pages/latest/10'
 */
class LatestQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return QueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $contentQuery->setDirective('order', '-id');
        $contentQuery->setDirective('limit', 1);
        $contentQuery->setDirective('returnsingle', true);

        return call_user_func_array($contentQuery->getHandler('select'), [$contentQuery]);
    }
}
