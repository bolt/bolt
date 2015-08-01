<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\SelectQuery;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/SelectQuery.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SearchQueryTest extends BoltUnitTest
{
    public function testQuery()
    {
        $app = $this->getApp();
        $this->addSomeContent();

        $filter = 'lorem ipsum';

        $query = $app['query.search'];
        $query->setContentType('pages');
        $query->setSearch($filter);
        $expr = $query->getWhereExpression();
        echo $expr;
    }
}
