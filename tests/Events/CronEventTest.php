<?php
namespace Bolt\Tests\Events;

use Bolt\Cache;
use Bolt\Events\CronEvent;
use Bolt\Events\CronEvents;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class to test src/Events/CronEvent.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CronEventTest extends BoltUnitTest
{
    public function testCronCalls()
    {
        /*
        $app = $this->getApp();

        $app['cache'] = $this->getMock('Bolt\Cache');
        $app['logger.manager'] = $this->getMock('Bolt\Logger\Manager', array('trim'), array($app));

        $app['cache']->expects($this->exactly(1))
                  ->method('clearCache');

        $app['logger.manager']->expects($this->exactly(2))
                  ->method('trim');

        $output = new BufferedOutput();

        $listeningEvent = new CronEvent($app, $output);

        $app['dispatcher']->dispatch(CronEvents::CRON_HOURLY, $listeningEvent);
        $app['dispatcher']->dispatch(CronEvents::CRON_DAILY, $listeningEvent);
        $app['dispatcher']->dispatch(CronEvents::CRON_WEEKLY, $listeningEvent);
        $app['dispatcher']->dispatch(CronEvents::CRON_MONTHLY, $listeningEvent);
        $app['dispatcher']->dispatch(CronEvents::CRON_YEARLY, $listeningEvent);

        // Currently only the weekly produces any output for us to call
        $out = $listeningEvent->output->fetch();
        $this->assertRegExp('/Clearing cache/', $out);
        $this->assertRegExp('/Trimming logs/', $out);
*/
    }
}
