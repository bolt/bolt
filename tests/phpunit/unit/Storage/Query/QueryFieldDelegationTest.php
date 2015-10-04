<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Legacy\Storage;
use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage/Query/ContentQueryParser.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryFieldDelegationTest extends BoltUnitTest
{
    public function testTaxonomyFilter()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $this->addSomeContent();

        $test1 = $app['storage']->getContent('entries', ['categories' => 'news']);
        $test1count = count($test1);

        $test2 = $app['query']->getContent('entries', ['categories' => 'news']);
        $test2count = count($test2);

        $this->assertEquals($test1count, $test2count);
    }

    public function testRelationFilter()
    {
        $app = $this->getApp();

        $results = $app['query']->getContent('showcases', ['entries' => '1 || 2 || 3']);
        foreach ($results as $result) {
            foreach ($result->relation['entries'] as $entry) {
                $this->assertTrue(in_array($entry->id, [1, 2, 3]));
                $this->assertEquals('entries', $entry->getContentType());
            }
        }
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $app['config']->set('taxonomy/categories/options', ['news']);
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(['showcases', 'entries', 'pages']);

        // We also set some relations between showcases and entries
        $showcases = $storage->getContent('showcases');
        $randEntries = $storage->getContent('entries/random/2');
        foreach ($showcases as $show) {
            foreach (array_keys($randEntries) as $key) {
                $show->setRelation('entries', $key);
                $storage->saveContent($show);
            }
        }
    }
}
