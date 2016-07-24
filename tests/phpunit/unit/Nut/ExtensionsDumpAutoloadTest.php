<?php

namespace Bolt\Tests\Nut;

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

        $runner = $this->getMock('Bolt\Composer\PackageManager', ['dumpAuoloader'], [$app]);
        $runner->expects($this->any())
            ->method('dumpAuoloader')
            ->will($this->returnValue(0));

        $app['extend.manager'] = $runner;

        $command = new ExtensionsDumpAutoload($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Rebuilding autoloaders… \[DONE\]/', $result);
        $this->assertRegExp('/Generating optimized autoload files/', $result);
        $this->assertRegExp('/PackageEventListener::dump/', $result);
    }
}
