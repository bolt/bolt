<?php

namespace Bolt\Tests\Provider;

use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\PathServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PathServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertSame(PHPUNIT_WEBROOT, $app['path_resolver.root']);
        $this->assertSame(['web' => '.'], $app['path_resolver.paths']);
    }
}
