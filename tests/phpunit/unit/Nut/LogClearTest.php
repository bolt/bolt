<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\LogClear;
use Bolt\Tests\BoltFunctionalTestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/LogClear.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class LogClearTest extends BoltFunctionalTestCase
{
    public function testRun()
    {
        $app = $this->getApp();
        $command = new LogClear($app);
        $tester = new CommandTester($command);

        $helper = $this->getMock('\Symfony\Component\Console\Helper\QuestionHelper', ['ask']);
        $helper->expects($this->once())
            ->method('ask')
            ->will($this->returnValue(true));
        $set = new HelperSet(['question' => $helper]);
        $command->setHelperSet($set);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/System & change logs cleared/', $result);
    }
}
