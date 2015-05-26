<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\StorageServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/StorageServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StorageServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new StorageServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Bolt\Storage\EntityManager', $app['storage']);
        $this->assertInstanceOf('Bolt\Storage', $app['storage.legacy']);
        $app->boot();
    }
}
