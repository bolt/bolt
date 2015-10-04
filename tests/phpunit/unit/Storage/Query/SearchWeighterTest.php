<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/SelectQuery.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SearchWeighterTest extends BoltUnitTest
{
    public function testSimpleWeight()
    {
        $app = $this->getApp();
        $this->addSomeContent();

        $results = $app['query']->getContent('pages/first/3');
        $results = iterator_to_array($results);

        $weighter = $app['query.search_weighter'];
        $weighter->setResults($results);
        $weighter->setSearchWords(['lorem', 'ipsum']);
        $weighter->setContentType('pages');
        $scores = $weighter->weight();
        $this->assertTrue(is_array($scores));
        $this->assertEquals(count($results), count($scores));
    }

    public function testScoring()
    {
        $app = $this->getApp();
        $results = $app['query']->getContent('pages/first/3');
        $results = iterator_to_array($results);
        $results[2]->setTitle("Lorem Ipsum title to improve lorem ipsum result");
        $results[1]->setTitle("Lorem Ipsum");
        $results[1]->setBody("Lorem Ipsum");

        $weighter = $app['query.search_weighter'];
        $weighter->setResults($results);
        $weighter->setSearchWords(['lorem', 'ipsum']);
        $weighter->setContentType('pages');
        $scores = $weighter->weight();

        $this->assertGreaterThan($scores[0], $scores[2], 'message');
        $this->assertGreaterThan($scores[2], $scores[1], 'message');
    }
}
