<?php

namespace Bolt\Tests\Provider;

use Bolt\Config;
use Bolt\Tests\BoltUnitTest;

/**
 * @covers \Bolt\Provider\ConfigServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(Config::class, $app['config']);
    }
}
