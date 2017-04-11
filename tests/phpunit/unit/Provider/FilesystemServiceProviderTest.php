<?php

namespace Bolt\Tests\Provider;

use Bolt\Filesystem;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\FilesystemServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Filesystem\Manager::class, $app['filesystem']);
    }
}
