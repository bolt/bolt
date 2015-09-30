<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/ContentQueryParser.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentQueryParserTest extends BoltUnitTest
{
    public function testQueryParse()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('pages');
        $qb->parse();
        $this->assertEquals(['pages'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('page/about');
        $qb->parse();
        $this->assertEquals(['page'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('about', $qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('(pages,entries)/search');
        $qb->parse();
        $this->assertEquals(['pages', 'entries'], $qb->getContentTypes());
        $this->assertEquals('search', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('(pages,entries,showcases)/latest');
        $qb->parse();
        $this->assertEquals(['pages', 'entries', 'showcases'], $qb->getContentTypes());
        $this->assertEquals('latest', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('pages,entries/about');
        $qb->parse();
        $this->assertEquals(['pages', 'entries'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('about', $qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('pages/first/3');
        $qb->parse();
        $this->assertEquals(['pages'], $qb->getContentTypes());
        $this->assertEquals('first', $qb->getOperation());
        $this->assertEquals('3', $qb->getDirective('limit'));
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('pages,entries/search');
        $qb->parse();
        $this->assertEquals('search', $qb->getOperation());
        $this->assertEquals(['pages', 'entries'], $qb->getContentTypes());
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('page/5');
        $qb->parse();
        $this->assertEquals(['page'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('5', $qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('(entries,events)/random/10');
        $qb->parse();
        $this->assertEquals(['entries', 'events'], $qb->getContentTypes());
        $this->assertEquals('random', $qb->getOperation());
        $this->assertEquals('10', $qb->getDirective('limit'));
        $this->assertEmpty($qb->getIdentifier());
    }

    public function testDirectiveParsing()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('entries');
        $qb->setParameters(['order' => '-datepublish', 'id' => '!1']);
        $qb->parse();
        $this->assertEquals(['entries'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('-datepublish', $qb->getDirective('order'));
        $this->assertEquals('!1', $qb->getParameter('id'));
        $this->assertEquals(1, count($qb->getParameters()));
    }

    public function testPrintQuery()
    {
        $app = $this->getApp();
        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('entries');
        $qb->setParameters(['order' => '-datepublish', 'id' => '!1', 'printquery' => true]);
        $this->expectOutputString("SELECT entries.* FROM bolt_entries entries WHERE entries.id <> :id_1 ORDER BY datepublish DESC");
        $qb->fetch();
    }

    public function testGetQuery()
    {
        $app = $this->getApp();
        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('pages');
        $qb->setParameters(['order' => '-datepublish', 'id' => '!1', 'getquery' => function ($query) {
            echo $query;
        }]);
        $this->expectOutputString("SELECT pages.* FROM bolt_pages pages WHERE pages.id <> :id_1 ORDER BY datepublish DESC");
        $qb->fetch();
    }

    public function testMultipleOrder()
    {
        $app = $this->getApp();
        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('entries');
        $qb->setParameters(['order' => '-datepublish, title', 'getquery' => function ($query) {
            echo $query;
        }]);
        $this->expectOutputString("SELECT entries.* FROM bolt_entries entries ORDER BY datepublish DESC, title ASC");
        $qb->fetch();
    }

    public function testRandomHandler()
    {
        $app = $this->getApp();
        $this->addSomeContent($app);

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('(pages,showcases)/random/4');
        $res = $qb->fetch();

        $this->assertEquals(8, count($res));
    }

    public function testReturnSingleHandler()
    {
        $app = $this->getApp();
        $this->addSomeContent($app);

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('pages/random/4');
        $qb->setParameters(['returnsingle' => true]);
        $res = $qb->fetch();

        $this->assertInstanceOf('Bolt\Storage\Entity\Content', $res);
    }

    public function testFirstHandler()
    {
        $app = $this->getApp();
        $this->addSomeContent($app);

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('pages/first/4');
        $res = $qb->fetch();

        $this->assertEquals(4, count($res));
        $count = 1;
        foreach ($res as $item) {
            $this->assertEquals($count, $item['id']);
            $count++;
        }
    }

    public function testLatestHandler()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addSomeContent($app);

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('pages/latest/4');
        $res = $qb->fetch();

        $this->assertEquals(4, count($res));
        $count = 5;
        foreach ($res as $item) {
            $this->assertEquals($count, $item['id']);
            $count--;
        }
    }

    public function testSetParameter()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->setQuery('entries');
        $qb->setParameters(['order' => '-datepublish']);
        $qb->setParameter('id', '!1');
        $this->assertTrue(array_key_exists('id', $qb->getParameters()));
    }

    public function testAddOperation()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->addOperation('featured');

        $this->assertTrue(in_array('featured', $qb->getOperations()));
    }

    public function testRemoveOperation()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->addOperation('featured');
        $this->assertTrue(in_array('featured', $qb->getOperations()));
        $qb->removeOperation('featured');
        $this->assertFalse(in_array('featured', $qb->getOperations()));
    }

    public function testSearchHandler()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->addService('search', $app['query.search']);
        $qb->addService('search_weighter', $app['query.search_weighter']);
        $qb->setQuery('pages/search/4');
        $qb->setParameters(['filter' => 'lorem ipsum']);
        $res = $qb->fetch();
        $this->assertEquals(4, $res->count());
    }

    public function testNativeSearchHandlerFallback()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage'], $app['query.select']);
        $qb->addService('search', $app['query.search']);
        $qb->addService('search_weighter', $app['query.search_weighter']);
        $qb->setQuery('pages/nativesearch/4');
        $qb->setParameters(['filter' => 'lorem ipsum']);
        $res = $qb->fetch();
        $this->assertEquals(4, $res->count());
    }
}
