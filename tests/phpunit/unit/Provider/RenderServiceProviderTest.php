<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\RenderServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/RenderServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RenderServiceProviderTest extends BoltFunctionalTestCase
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
