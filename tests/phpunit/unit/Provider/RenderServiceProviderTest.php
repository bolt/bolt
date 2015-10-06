<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\RenderServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/RenderServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RenderServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app->register(new RenderServiceProvider());

        $this->assertInstanceOf('Bolt\Render', $app['render']);
        $this->assertInstanceOf('Bolt\Render', $app['safe_render']);

        $app->boot();
    }
}
