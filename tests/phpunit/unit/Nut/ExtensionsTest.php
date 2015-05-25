<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\Extensions;
use Bolt\Tests\BoltUnitTest;
use Composer\Package\CompletePackage;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\TableHelper;
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

        $runner = $this->getMock("Bolt\Composer\PackageManager", ['showPackage'], [$app]);
        $runner->expects($this->any())
            ->method('showPackage')
            ->will($this->returnValue(['test' => ['package' => $testPackage]]));

        $app['extend.manager'] = $runner;

        $command = new Extensions($app);
        $command->setHelperSet(new HelperSet([new TableHelper()]));
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Name.*Version/', $result);
        $this->assertRegExp('/test.*1.0/', $result);
    }
}
