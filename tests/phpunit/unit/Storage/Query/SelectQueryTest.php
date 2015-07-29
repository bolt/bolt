<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\SelectQuery;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/SelectQuery.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SelectQueryTest extends BoltUnitTest
{
    public function testQuery()
    {
        $app = $this->getApp();

        $filters = ['username' => '%fred%', 'email' => '%fred', 'status' => 'published'];

        $query = new SelectQuery($app['storage']->createQueryBuilder(), $app['query.parser.handler']);
        $query->setContentType('pages');
        $query->setParameters(($filters));
        $expr = $query->getWhereExpression();
        $this->assertEquals('(pages.username LIKE :username_1) AND (pages.email LIKE :email_1) AND (pages.status = :status_1)', $expr->__toString());
        $this->assertEquals(['%fred%', '%fred', 'published'], array_values($query->getWhereParameters()));
    }
}
