<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\ExtensionsSetup;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test Bolt\Nut\ExtensionsSetup class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsSetupTest extends BoltUnitTest
{
    public function testRun()
    {
        $app = $this->getApp();
        $app['extend.action.options']->set('optimize-autoloader', true);

        $command = new ExtensionsSetup($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Creating\/updating composer.json… \[DONE\]/', $result);

        $this->assertRegExp('/Updating autoloaders… \[DONE\]/', $result);
        $this->assertRegExp('/Generating optimized autoload files/', $result);
        $this->assertRegExp('/PackageEventListener::dump/', $result);
    }

    public function testRunWithLocal()
    {
        $app = $this->getApp();
        $app['extend.action.options']->set('optimize-autoloader', true);
        $app['filesystem']->createDir('extensions://local');

        $command = new ExtensionsSetup($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();

        $app['filesystem']->deleteDir('extensions://local');

        $this->assertRegExp('/Creating\/updating composer.json… \[DONE\]/', $result);

        $this->assertRegExp('/Installing merge plugin for local extension support… \[DONE\]/', $result);
        $this->assertRegExp('/Loading composer repositories with package information/', $result);
        $this->assertRegExp('/Updating dependencies/', $result);
        $this->assertRegExp('/Installing wikimedia\/composer-merge-plugin/', $result);

        $this->assertRegExp('/Updating autoloaders… \[DONE\]/', $result);
        $this->assertRegExp('/Generating optimized autoload files/', $result);
        $this->assertRegExp('/PackageEventListener::dump/', $result);
    }
}
