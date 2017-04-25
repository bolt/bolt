<?php

namespace Bolt\Tests\Nut;

use Bolt\Composer\PackageManager;
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

        $runner = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['removePackage'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $runner->expects($this->any())
            ->method('removePackage')
            ->will($this->returnValue(0));

        $this->setService('extend.manager', $runner);

        $command = new ExtensionsUninstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Removed extension test/', $result);
    }

    /**
     * @group slow
     */
    public function testFailed()
    {
        $app = $this->getApp();

        $runner = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['removePackage'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $runner->expects($this->any())
            ->method('removePackage')
            ->will($this->returnValue(1));

        $this->setService('extend.manager', $runner);

        $command = new ExtensionsUninstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Unable to remove extension test/', $result);
    }
}
