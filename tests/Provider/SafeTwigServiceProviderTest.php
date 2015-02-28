<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\SafeTwigServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/SafeTwigServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SafeTwigServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new SafeTwigServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Twig_Environment', $app['safe_twig']);
        $app->boot();
    }
}
