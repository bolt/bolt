<?php

namespace Bolt\Tests\Provider;

use Bolt\Omnisearch;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\OmnisearchServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class OmnisearchServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Omnisearch::class, $app['omnisearch']);
    }
}
