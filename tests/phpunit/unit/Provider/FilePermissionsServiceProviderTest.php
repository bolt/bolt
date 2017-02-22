<?php

namespace Bolt\Tests\Provider;

use Bolt\Filesystem\FilePermissions;
use Bolt\Provider\FilePermissionsServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/FilePermissionsServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FilePermissionsServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new FilePermissionsServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(FilePermissions::class, $app['filepermissions']);
        $app->boot();
    }
}
