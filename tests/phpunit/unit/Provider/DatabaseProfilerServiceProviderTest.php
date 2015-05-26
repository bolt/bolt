<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\DatabaseProfilerServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/DatabaseProfilerServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseProfilerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app['data_collector.templates'] = [];
        $app['data_collectors'] = [];
        $provider = new DatabaseProfilerServiceProvider($app);
        $app->register($provider);
        $this->assertInstanceOf('Doctrine\DBAL\Logging\DebugStack', $app['db.logger']);

        $logger = $app['data_collectors']['db']->__invoke($app);
        $this->assertInstanceOf('Bolt\DataCollector\DatabaseDataCollector', $logger);

        $app->boot();
    }
}
