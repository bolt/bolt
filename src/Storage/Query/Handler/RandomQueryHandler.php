<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryResultset;

/**
 *  Handler for queries requested with the random modifier.
 *
 *  eg: 'pages/random/10'
 */
class RandomQueryHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     *
     * @return QueryResultset
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $params = $contentQuery->getEntityManager()->createQueryBuilder()->getConnection()->getParams();
        if (strpos($params['driver'], 'mysql') !== false) {
            $contentQuery->setDirective('order', 'RAND()');
        } else {
            $contentQuery->setDirective('order', 'RANDOM()');
        }

        return call_user_func($contentQuery->getHandler('select'), $contentQuery);
    }
}
