<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\StackServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/StackServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StackServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new StackServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Stack', $app['stack']);
        $app->boot();
    }
}
