<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\Info;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/Info.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class InfoTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new Info($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/(PHP Version|HipHop)/', $result);
    }
}
