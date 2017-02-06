<?php

namespace Bolt\Tests\Provider;

use Bolt\Legacy\Storage;
use Bolt\Provider\StorageServiceProvider;
use Bolt\Storage\EntityManager;
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
        $this->assertInstanceOf(EntityManager::class, $app['storage']);
        $this->assertInstanceOf(Storage::class, $app['storage.legacy']);
        $app->boot();
    }
}
