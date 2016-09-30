<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\Init;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/Hello.
 *
 * @author Bob den Otter <bob@bolt.cm>
 */
class HelloTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new Init($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Welcome to Bolt/', $result);
    }
}
