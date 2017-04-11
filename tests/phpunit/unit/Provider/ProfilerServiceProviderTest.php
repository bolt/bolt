<?php

namespace Bolt\Tests\Provider;

use Bolt\Profiler\BoltDataCollector;
use Bolt\Profiler\DatabaseDataCollector;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL\Logging\DebugStack;

/**
 * @covers \Bolt\Provider\ProfilerServiceProvider
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

        $templates = $app['data_collector.templates'];
        $this->assertSame('bolt', $templates[0][0]);
        $this->assertSame('db', end($templates)[0]);
        $this->assertSame('@BoltProfiler/config.html.twig', $templates[1][1]);

        $collectors = $app['data_collectors'];
        $this->assertArrayHasKey('bolt', $collectors);
        $this->assertArrayHasKey('db', $collectors);
        $this->assertInstanceOf(BoltDataCollector::class, $collectors['bolt']->__invoke($app));
        $this->assertInstanceOf(DatabaseDataCollector::class, $collectors['db']->__invoke($app));

        $this->assertNotEmpty($app['twig.loader.bolt_filesystem']->getPaths('BoltProfiler'));

        $logger = $app['db.logger'];
        $this->assertInstanceOf(DebugStack::class, $logger);

        $app->boot();

        $this->assertSame($logger, $app['db.config']->getSQLLogger());
    }
}
