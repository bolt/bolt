<?php

namespace Bolt\Tests\Provider;

use Bolt\Composer\Satis\StatService;
use Bolt\Extension;
use Bolt\Provider\ExtensionServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/ExtensionServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $provider = new ExtensionServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf(Extension\Manager::class, $app['extensions']);
        $this->assertInstanceOf(StatService::class, $app['extensions.stats']);
        $app->boot();
    }
}
