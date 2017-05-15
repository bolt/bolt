<?php

namespace Bolt\Tests\Provider;

use Bolt\AccessControl;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\AccessControlServiceProvider
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessControlServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(AccessControl\AccessChecker::class, $app['access_control']);
        $this->assertInstanceOf(AccessControl\Login::class, $app['access_control.login']);
        $this->assertInstanceOf(AccessControl\Password::class, $app['access_control.password']);

        $cookieOptions = $app['access_control.cookie.options'];
        $this->assertArrayHasKey('remoteaddr', $cookieOptions);
        $this->assertArrayHasKey('remoteaddr', $cookieOptions);
    }
}
