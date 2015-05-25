<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\ExtensionsEnable;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ExtensionsEnable.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionsEnableTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $runner = $this->getMock("Bolt\Composer\PackageManager", ['requirePackage'], [$app]);
        $runner->expects($this->any())
            ->method('requirePackage')
            ->will($this->returnValue(0));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsEnable($app);
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'test', 'version' => '1.0']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/[Done]/', trim($result));
    }
}
