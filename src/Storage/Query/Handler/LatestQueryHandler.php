<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;

/**
 *  
 */
class LatestQueryHandler
{
    public function __invoke(ContentQueryParser $contentQuery)
    {
        $contentQuery->setDirective('order', '-id');
        $contentQuery->setDirective('limit', 1);
        $contentQuery->setDirective('returnsingle', true);
        return call_user_func_array($contentQuery->getHandler('select'), [$contentQuery]);
    }
}
