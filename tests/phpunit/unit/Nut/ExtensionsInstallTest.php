<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\ExtensionsInstall;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ExtensionsEnable.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsInstallTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $runner = $this->getMock('Bolt\Composer\PackageManager', ['requirePackage'], [$app]);
        $runner->expects($this->any())
            ->method('requirePackage')
            ->will($this->returnValue(0));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsInstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test', 'version' => '1.0']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Starting install of test:1.0… \[DONE\]/', trim($result));
    }

    public function testFailed()
    {
        $app = $this->getApp();

        $runner = $this->getMock('Bolt\Composer\PackageManager', ['requirePackage'], [$app]);
        $runner->expects($this->any())
            ->method('requirePackage')
            ->will($this->returnValue(1));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsInstall($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test', 'version' => '1.0']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Starting install of test:1.0… \[FAILED\]/', trim($result));
    }
}
