<?php

namespace Bolt\Tests\Provider;

use Bolt\Cache;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\CacheServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CacheServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Cache::class, $app['cache']);
    }
}
