<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\BoltProfilerServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/BoltProfilerServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltProfilerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app['data_collector.templates'] = [];
        $app['data_collectors'] = [];
        $provider = new BoltProfilerServiceProvider($app);
        $app->register($provider);
        $this->assertNotEmpty($app['data_collector.templates']);
        $collectors = $app['data_collectors'];
        $bolt = $collectors['bolt']->__invoke($app);
        $this->assertInstanceOf('Bolt\DataCollector\BoltDataCollector', $bolt);

        $app->boot();
    }
}
