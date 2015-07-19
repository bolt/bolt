<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\Query\SelectQuery;

/**
 * Class to test src/Storage/Query/SelectQuery.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class selectQueryTest extends BoltUnitTest
{
    public function testQuery()
    {
        $app = $this->getApp();
        
        $qb = $app['storage']->createQueryBuilder();
        
        $contenttypes = ['pages'];
        $filters = ['username'=>'%fred%', 'email'=>'%fred', 'status'=>'published'];
        
        $query = new SelectQuery($qb, $contenttypes, $filters);
        $expr = $query->getWhereExpression();
        $this->assertEquals('(username LIKE :username_1) AND (email LIKE :email_1) AND (status = :status_1)', $expr->__toString());
        $this->assertEquals(['%fred%','%fred','published'], $query->getWhereParameters());
    }
    
}