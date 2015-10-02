<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/SearchQuery.
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
        $this->assertEquals('((pages.title LIKE :title_1) OR (pages.title LIKE :title_2)) OR ((pages.teaser LIKE :teaser_1) OR (pages.teaser LIKE :teaser_2)) OR ((pages.body LIKE :body_1) OR (pages.body LIKE :body_2)) OR ((pages.chapters LIKE :chapters_1) OR (pages.chapters LIKE :chapters_2))', $expr);
        $params = $query->getWhereParameters();
        $this->assertArrayHasKey('title_1', $params);
        $this->assertArrayHasKey('title_2', $params);
        $this->assertArrayHasKey('teaser_1', $params);
        $this->assertArrayHasKey('teaser_2', $params);
        $this->assertArrayHasKey('body_1', $params);
        $this->assertArrayHasKey('body_2', $params);
        $this->assertArrayHasKey('chapters_1', $params);
        $this->assertArrayHasKey('chapters_2', $params);
        $this->assertEquals('%lorem%', $params['title_1']);
        $this->assertEquals('%ipsum%', $params['title_2']);
    }

    public function testAndParameterQuery()
    {
        $app = $this->getApp();
        $filter = 'lorem + ipsum';
        $query = $app['query.search'];
        $query->setContentType('pages');
        $query->setSearch($filter);
        $expr = $query->getWhereExpression();
        $this->assertEquals('((pages.title LIKE :title_1) AND (pages.title LIKE :title_2)) OR ((pages.teaser LIKE :teaser_1) AND (pages.teaser LIKE :teaser_2)) OR ((pages.body LIKE :body_1) AND (pages.body LIKE :body_2)) OR ((pages.chapters LIKE :chapters_1) AND (pages.chapters LIKE :chapters_2))', $expr);
        $params = $query->getWhereParameters();
        $this->assertArrayHasKey('title_1', $params);
        $this->assertArrayHasKey('title_2', $params);
        $this->assertArrayHasKey('teaser_1', $params);
        $this->assertArrayHasKey('teaser_2', $params);
        $this->assertArrayHasKey('body_1', $params);
        $this->assertArrayHasKey('body_2', $params);
        $this->assertArrayHasKey('chapters_1', $params);
        $this->assertArrayHasKey('chapters_2', $params);
        $this->assertEquals('%lorem%', $params['title_1']);
        $this->assertEquals('%ipsum%', $params['title_2']);
        $this->assertEquals('%lorem%', $params['teaser_1']);
        $this->assertEquals('%ipsum%', $params['teaser_2']);
        $this->assertEquals('%lorem%', $params['body_1']);
        $this->assertEquals('%ipsum%', $params['body_2']);
        $this->assertEquals('%lorem%', $params['chapters_1']);
        $this->assertEquals('%ipsum%', $params['chapters_2']);
    }

    public function testContenttypeFailure()
    {
        $app = $this->getApp();
        $filter = 'main other';
        $query = $app['query.search'];
        $query->setContentType('showcases');
        $this->setExpectedException('Bolt\Exception\QueryParseException');
        $query->setSearch($filter);
    }

    public function testMissingContenttypeFailure()
    {
        $app = $this->getApp();
        $filter = 'main other';
        $query = $app['query.search'];
        $query->setContentType('nonexistent');
        $this->setExpectedException('Bolt\Exception\QueryParseException');
        $query->setSearch($filter);
    }
}
