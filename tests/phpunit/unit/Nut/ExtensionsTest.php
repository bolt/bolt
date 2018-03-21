<?php

namespace Bolt\Tests\Nut;

use Bolt\Composer\PackageManager;
use Bolt\Nut\Extensions;
use Bolt\Tests\BoltUnitTest;
use Composer\Package\CompletePackage;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/Extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtensionsTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $testPackage = new CompletePackage('test', '1.0.1', '1.0');
        $testPackage->setDescription('An extension');
        $testPackage->setType('bolt-extension');

        $runner = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['showPackage'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $runner->expects($this->any())
            ->method('showPackage')
            ->will($this->returnValue(['test' => ['package' => $testPackage]]));

        $this->setService('extend.manager', $runner);

        $command = new Extensions($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Name.*Version/', $result);
        $this->assertRegExp('/test.*1.0/', $result);
    }
}
