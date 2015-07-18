<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\Query\SelectQuery;

/**
 * Class to test src/Storage/Query/SelectQuery.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentQueryParserTest extends BoltUnitTest
{
    public function testQuery()
    {
        $app = $this->getApp();
        
        $qb = $app['storage']->createQueryBuilder();
        //$query = new SelectQuery($qb);
    }
    
}