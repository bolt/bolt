<?php

namespace Bolt\Tests\Storage\Query\Directive;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Storage\Query\Directive\PagingDirective
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PagingDirectiveTest extends BoltUnitTest
{
    public function testInputParameters()
    {
        $app = $this->getApp();

        $qb = new ContentQueryParser($app['storage']);
        $qb->setQuery('pages');
        $qb->setParameters(['page' => '5']);
        $qb->parse();
        $this->assertEquals(['pages'], $qb->getContentTypes());
        $this->assertEquals('select', $qb->getOperation());
        $this->assertEmpty($qb->getIdentifier());
    }
}
