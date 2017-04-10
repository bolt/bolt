<?php

namespace Bolt\Tests\Provider;

use Bolt\Legacy\Storage;
use Bolt\Storage\EntityManager;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\StorageServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StorageServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(EntityManager::class, $app['storage']);
        $this->assertInstanceOf(Storage::class, $app['storage.legacy']);
    }
}
