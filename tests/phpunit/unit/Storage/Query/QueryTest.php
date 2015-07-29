<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/QueryTest.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryTest extends BoltUnitTest
{
    public function testgetContent()
    {
        $app = $this->getApp();
        $this->addSomeContent();

        $results = $app['query']->getContent('pages', ['id' => '<10']);

        $this->assertInstanceOf('Bolt\Storage\Query\QueryResultset', $results);

        $results = $app['query']->getContent('pages', ['datepublish' => '>now || !last week', 'datedepublish' => '<1 year ago']);
        $this->assertInstanceOf('Bolt\Storage\Query\QueryResultset', $results);
    }

    public function testGetContentReturnSingle()
    {
        $app = $this->getApp();
        $this->addSomeContent();

        $results = $app['query']->getContent('pages', ['id' => '<10', 'returnsingle' => true]);
        $this->assertEquals(1, count($results));
    }
}
