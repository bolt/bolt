<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\LoggerServiceProvider;
use Bolt\Tests\BoltUnitTest;

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
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $app['logger.system']);
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $app['logger.change']);
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $app['logger.firebug']);
        $this->assertInstanceOf('Bolt\Logger\Manager', $app['logger.manager']);

        $app->boot();
    }
}
