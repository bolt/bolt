<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\LogClear;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/LogClear.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class LogClearTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new LogClear($app);
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream("Yes\n"));
        $command->setHelperSet(new HelperSet([$dialog]));
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/System & change logs cleared/', $result);
    }

    // public function testCancel()
    // {
    //     $app = $this->getApp();
    //     // Test abort
    //     $command = new LogClear($app);
    //     $command->setHelperSet(new Helperset([new DialogHelper]));
    //     $dialog = $command->getHelper('dialog');
    //     $dialog->setInputStream($this->getInputStream("Test\n"));
    //     $tester = new CommandTester($command);

    //     $tester->execute([]);
    //     $result = $tester->getDisplay();
    //     $this->assertNotRegexp('/Activity logs cleared/', $result);
    // }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
