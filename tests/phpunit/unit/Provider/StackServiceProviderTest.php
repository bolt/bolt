<?php

namespace Bolt\Tests\Provider;

use Bolt\Stack;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\StackServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StackServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Stack::class, $app['stack']);
    }
}
