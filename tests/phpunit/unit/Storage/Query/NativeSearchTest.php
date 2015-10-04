<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\Adapter\PostgresSearch;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/QueryTest.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class NativeSearchTest extends BoltUnitTest
{
    public function testPostgresQueryBuild()
    {
        $app = $this->getApp();
        $this->addSomeContent();

        $repo = $app['storage']->getRepository('bolt_pages');
        $query = $repo->createQueryBuilder('pages');
        $handler = new PostgresSearch($query, $app['query.search_config'], explode(' ', 'lorem ipsum'));
        $handler->setContentType('pages');
        $query = $handler->getQuery();

        $this->assertEquals(
            ['pages.*', "ts_rank(bsearch.document, to_tsquery('lorem&ipsum')) as score"],
            $query->getQueryPart('select')
        );
        $this->assertEquals([
                ['table' => 'bolt_pages', 'alias' => 'pages'],
                [
                    'table' => "(SELECT *, setweight(to_tsvector(pages.title), 'A') || setweight(to_tsvector(pages.teaser), 'B') || setweight(to_tsvector(pages.body), 'B') AS document FROM bolt_pages pages GROUP BY pages.id)",
                    'alias' => 'bsearch'
                ]
            ],
            $query->getQueryPart('from')
        );
        $this->assertInstanceOf('Doctrine\DBAL\Query\Expression\CompositeExpression', $query->getQueryPart('where'));
        $this->assertEquals(['score DESC'], $query->getQueryPart('orderBy'));
    }
}
