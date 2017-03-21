<?php

namespace Bolt\Tests\Provider;

use Bolt\Provider\RenderServiceProvider;
use Bolt\Render;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/RenderServiceProvider.
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
        $app->register(new RenderServiceProvider());

        $this->assertInstanceOf(Render::class, $app['render']);
        $this->assertInstanceOf(Render::class, $app['safe_render']);

        $app->boot();
    }
}
