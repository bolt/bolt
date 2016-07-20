<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\CronRunner;
use Bolt\Tests\BoltFunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/CronRunner.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CronRunnerTest extends BoltFunctionalTestCase
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new CronRunner($app);
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
