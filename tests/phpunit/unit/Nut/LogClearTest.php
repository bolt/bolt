<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\LogClear;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/LogClear.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogClearTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new LogClear($app);
        $command->addOption('--no-interaction');
        $tester = new CommandTester($command);

        $tester->execute(['--no-interaction' => true]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/System & change logs cleared/', $result);
    }
}
