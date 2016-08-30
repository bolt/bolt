<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\ExtensionsUninstall;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ExtensionsDisable.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsUninstallTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $runner = $this->getMock('Bolt\Composer\PackageManager', ['removePackage'], [$app]);
        $runner->expects($this->any())
            ->method('removePackage')
            ->will($this->returnValue(0));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsUninstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Starting uninstall of test… \[DONE\]/', trim($result));
    }

    public function testFailed()
    {
        $app = $this->getApp();

        $runner = $this->getMock('Bolt\Composer\PackageManager', ['removePackage'], [$app]);
        $runner->expects($this->any())
            ->method('removePackage')
            ->will($this->returnValue(1));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsUninstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Starting uninstall of test… \[FAILED\]/', trim($result));
    }
}
