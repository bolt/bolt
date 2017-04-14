<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\PimpleDump;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Bolt\Nut\PimpleDump
 * @group slow
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PimpleDumpTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $app['pimpledump.output_dir'] = PHPUNIT_WEBROOT;
        $command = new PimpleDump($app);

        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertFileExists(PHPUNIT_WEBROOT . '/pimple.json');
    }

    public function tearDown()
    {
        @unlink(PHPUNIT_WEBROOT . '/pimple.json');
    }
}
