<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\StackServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/StackServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StackServiceProviderTest extends BoltFunctionalTestCase
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
