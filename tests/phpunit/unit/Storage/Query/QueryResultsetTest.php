<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\QueryResultset;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/QueryTest.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryResultsetTest extends BoltUnitTest
{
    public function testSimpleMerge()
    {
        $array1 = ['test1', 'test2', 'test3'];
        $array2 = ['test4', 'test5', 'test6'];

        $set = new QueryResultset();
        $set->add($array1);
        $set->add($array2);

        $this->assertEquals(6, iterator_count($set));
    }

    public function testLabelFetch()
    {
        $array1 = ['test1', 'test2', 'test3'];
        $array2 = ['test4', 'test5', 'test6'];

        $set = new QueryResultset();
        $set->add($array1, 'set1');
        $set->add($array2, 'set2');

        $this->assertEquals(6, iterator_count($set));
        $this->assertEquals(3, count($set->get('set1')));
        $this->assertEquals(3, count($set->get('set2')));
        $this->assertEquals(6, count($set->get()));
    }

    public function testNoLabelFetch()
    {
        $array1 = ['test1', 'test2', 'test3'];
        $array2 = ['test4', 'test5', 'test6'];

        $set = new QueryResultset();
        $set->add($array1);
        $set->add($array2);
        $this->assertEquals(6, count($set->get()));
    }
}
