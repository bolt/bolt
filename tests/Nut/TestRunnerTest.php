<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\TestRunner;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/TestRunner.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TestRunnerTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new TestRunner($app);
        $tester = new CommandTester($command);

        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegexp("/phpunit/", $result);
    }
}

namespace Bolt\Nut;

function system($command)
{
    return $command;
}
