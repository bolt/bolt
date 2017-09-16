<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\Cron;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Bolt\Nut\Cron
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CronTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new Cron($app);
        $tester = new CommandTester($command);

        $events = [];
        $app['dispatcher']->addListener(
            'cron.Hourly',
            function () use (&$events) {
                $events[] = 'cron.Hourly';
            }
        );

        $tester->execute(['--run' => 'cron.Hourly']);
        $result = $tester->getDisplay();
        $this->assertContains('cron.Hourly', $events);

        // Test no event doesn't run
        $tester->execute([]);
        $this->assertEquals(1, count($events));
    }
}
