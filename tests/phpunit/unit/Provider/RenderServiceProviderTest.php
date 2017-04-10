<?php

namespace Bolt\Tests\Provider;

use Bolt\Render;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\RenderServiceProvider
 *
 * @group legacy
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RenderServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Render::class, $app['render']);
        $this->assertInstanceOf(Render::class, $app['safe_render']);
    }
}
