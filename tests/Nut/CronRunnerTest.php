<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\CronRunner;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/CronRunner.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class CronRunnerTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();
        $command = new CronRunner($app);
        $tester = new CommandTester($command);
        
        $events = array();
        $app['dispatcher']->addListener('cron.Hourly', function () use (&$events) { $events[] = 'cron.Hourly'; });
        
        $tester->execute(array('--run'=>'cron.Hourly'));
        $result = $tester->getDisplay();
        $this->assertContains('cron.Hourly', $events);
        
        // Test no event doesn't run
        $tester->execute(array());
        $this->assertEquals(1, count($events));

    }
    
 
   
}