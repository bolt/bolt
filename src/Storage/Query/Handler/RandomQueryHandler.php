<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;

/**
 *  
 */
class RandomQueryHandler
{
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $params = $contentQuery->getEntityManager()->createQueryBuilder()->getConnection()->getParams();
        if (strpos($params['driver'], 'mysql') !== false ) {
            $contentQuery->setDirective('order', 'RAND()');
        } else {
            $contentQuery->setDirective('order', 'RANDOM()');
        }
           
        return call_user_func_array($contentQuery->getHandler('select'), [$contentQuery]);
    }
}
