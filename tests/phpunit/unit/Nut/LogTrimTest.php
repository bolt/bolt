<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\LogTrim;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/LogTrim.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LogTrimTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new LogTrim($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/System & change logs trimmed/', $result);
    }
}
