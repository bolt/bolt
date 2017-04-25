<?php

namespace Bolt\Tests\Nut;

use Bolt\Composer\PackageManager;
use Bolt\Nut\ExtensionsDumpAutoload;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test Bolt\Nut\ExtensionsDumpAutoload class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsDumpAutoloadTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $app['extend.action.options']->set('optimize-autoloader', true);

        $runner = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['dumpAuoloader'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $runner->expects($this->any())
            ->method('dumpAuoloader')
            ->will($this->returnValue(0));

        $this->setService('extend.manager', $runner);

        $command = new ExtensionsDumpAutoload($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Rebuilding extension autoloaders/', $result);
        $this->assertRegExp('/Generating optimized autoload files/', $result);
        $this->assertRegExp('/PackageEventListener::dump/', $result);
    }
}
