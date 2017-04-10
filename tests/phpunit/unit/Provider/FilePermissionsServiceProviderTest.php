<?php

namespace Bolt\Tests\Provider;

use Bolt\Filesystem\FilePermissions;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\FilePermissionsServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FilePermissionsServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(FilePermissions::class, $app['filepermissions']);
    }
}
