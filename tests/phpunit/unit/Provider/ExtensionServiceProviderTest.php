<?php

namespace Bolt\Tests\Provider;

use Bolt\Composer\Satis\StatService;
use Bolt\Extension;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\ExtensionServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Extension\Manager::class, $app['extensions']);
        $this->assertInstanceOf(StatService::class, $app['extensions.stats']);
    }
}
