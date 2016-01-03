<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\ExtensionsAutoloader;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test Bolt\Nut\ExtensionsAutoloader class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsAutoloaderTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();

        $runner = $this->getMock('Bolt\Composer\PackageManager', ['dumpAuoloader'], [$app]);
        $runner->expects($this->any())
            ->method('dumpAuoloader')
            ->will($this->returnValue(0));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsAutoloader($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Updating autoloaders… \[DONE\]/', $result);
        $this->assertRegExp('/Generating optimized autoload files/', $result);
        $this->assertRegExp('/PackageEventListener::dump/', $result);
    }
}
