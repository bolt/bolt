<?php

namespace Bolt\Tests\Provider;

use Bolt\Logger;
use Bolt\Provider\LoggerServiceProvider;
use Bolt\Tests\BoltUnitTest;
use Psr\Log\LoggerInterface;

/**
 * Class to test src/Provider/NutServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LoggerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new LoggerServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(LoggerInterface::class, $app['logger.system']);
        $this->assertInstanceOf(LoggerInterface::class, $app['logger.change']);
        $this->assertInstanceOf(LoggerInterface::class, $app['logger.firebug']);
        $this->assertInstanceOf(Logger\Manager::class, $app['logger.manager']);

        $app->boot();
    }
}
