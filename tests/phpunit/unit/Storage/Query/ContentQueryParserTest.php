<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\Query\ContentQueryParser;

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
        
        $qb = new ContentQueryParser($app['storage'], 'pages');
        $qb->parse();
        $this->assertEquals(['pages'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage'], 'page/about');
        $qb->parse();
        $this->assertEquals(['page'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('about', $qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage'], '(pages,entries)/search');
        $qb->parse();
        $this->assertEquals(['pages','entries'], $qb->getContentTypes());
        $this->assertEquals('search', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());
        
        
        
        $qb = new ContentQueryParser($app['storage'], '(pages,entries,showcases)/latest');
        $qb->parse();
        $this->assertEquals(['pages','entries','showcases'], $qb->getContentTypes());
        $this->assertEquals('latest', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());
        
        $qb = new ContentQueryParser($app['storage'], 'pages,entries/about');
        $qb->parse();
        $this->assertEquals(['pages','entries'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('about', $qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage'], 'pages/first/3');
        $qb->parse();
        $this->assertEquals(['pages'], $qb->getContentTypes());
        $this->assertEquals('first', $qb->getOperation());
        $this->assertEquals('3', $qb->getDirective('limit'));
        $this->assertEmpty($qb->getIdentifier());

        $qb = new ContentQueryParser($app['storage'], 'pages,entries/search');
        $qb->parse();
        $this->assertEquals('search', $qb->getOperation());
        $this->assertEquals(['pages','entries'], $qb->getContentTypes());
        $this->assertEmpty($qb->getIdentifier());
        
        $qb = new ContentQueryParser($app['storage'], 'page/5');
        $qb->parse();
        $this->assertEquals(['page'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEquals('5', $qb->getIdentifier());
        
        $qb = new ContentQueryParser($app['storage'], '(entries,events)/random/10');
        $qb->parse();
        $this->assertEquals(['entries','events'], $qb->getContentTypes());
        $this->assertEquals('random', $qb->getOperation());
        $this->assertEquals('10', $qb->getDirective('limit'));
        $this->assertEmpty($qb->getIdentifier());
    }
    
    public function testDirectiveParsing()
    {
        $app = $this->getApp();
        
        $qb = new ContentQueryParser($app['storage'], 'entries', ['order'=>'-datepublish', 'id'=>'!1']);
        $qb->addService('select', $app['query.select']);
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
        $qb = new ContentQueryParser($app['storage'], 'entries', ['order'=>'-datepublish', 'id'=>'!1', 'printquery'=>true]);
        $qb->addService('select', $app['query.select']);        
        $this->expectOutputString("SELECT entries.* FROM bolt_entries entries WHERE entries.id <> :id_1 ORDER BY datepublish DESC");
        $qb->fetch();

    }
    

}
