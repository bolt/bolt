<?php

namespace Bolt\Tests\Provider;

use Bolt\Logger;
use Bolt\Tests\BoltUnitTest;
use Psr\Log\LoggerInterface;

/**
 * @covers \Bolt\Provider\LoggerServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LoggerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertInstanceOf(LoggerInterface::class, $app['logger.system']);
        $this->assertInstanceOf(LoggerInterface::class, $app['logger.change']);
        $this->assertInstanceOf(LoggerInterface::class, $app['logger.firebug']);
        $this->assertInstanceOf(Logger\Manager::class, $app['logger.manager']);
    }
}
