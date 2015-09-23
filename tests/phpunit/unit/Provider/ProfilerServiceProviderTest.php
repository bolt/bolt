<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\ProfilerServiceProvider;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Provider/DatabaseProfilerServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class ProfilerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp(false);
        $app['debug'] = true;

        $app->register(new ProfilerServiceProvider());

        $templates = $app['data_collector.templates'];
        $this->assertSame('bolt', $templates[0][0]);
        $this->assertSame('db', end($templates)[0]);
        $this->assertSame('@BoltProfiler/config.html.twig', $templates[1][1]);

        $collectors = $app['data_collectors'];
        $this->assertArrayHasKey('bolt', $collectors);
        $this->assertArrayHasKey('db', $collectors);
        $this->assertInstanceOf('Bolt\Profiler\BoltDataCollector', $collectors['bolt']->__invoke($app));
        $this->assertInstanceOf('Bolt\Profiler\DatabaseDataCollector', $collectors['db']->__invoke($app));

        $this->assertNotEmpty($app['twig.loader.filesystem']->getPaths('BoltProfiler'));

        $logger = $app['db.logger'];
        $this->assertInstanceOf('Doctrine\DBAL\Logging\DebugStack', $logger);

        $app->boot();

        $this->assertSame($logger, $app['db.config']->getSQLLogger());
    }
}
