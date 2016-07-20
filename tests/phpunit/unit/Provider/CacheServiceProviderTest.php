<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\CacheServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;

/**
 * Class to test src/Provider/CacheServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CacheServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new CacheServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Cache', $app['cache']);
        $app->boot();
    }
}
