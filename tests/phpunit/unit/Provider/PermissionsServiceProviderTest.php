<?php

namespace Bolt\Tests\Provider;

use Bolt\AccessControl\Permissions;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\PermissionsServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PermissionsServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Permissions::class, $app['permissions']);
    }
}
