<?php

namespace Bolt\Tests\Nut;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Nut\ConfigGet;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ConfigGet.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigGetTest extends BoltUnitTest
{
    public function testGet()
    {
        $app = $this->getApp();
        $filesystem = new Filesystem(new Local(PHPUNIT_ROOT . '/resources/'));
        $app['filesystem']->mountFilesystem('config', $filesystem);

        $command = new ConfigGet($app);
        $tester = new CommandTester($command);
        $tester->execute(['key' => 'sitename', '--file' => 'config.yml']);
        $this->assertRegExp('/sitename: A sample site/', $tester->getDisplay());

        // test invalid
        $tester = new CommandTester($command);
        $tester->execute(['key' => 'nonexistent', '--file' => 'config.yml']);
        $this->assertRegExp("/The key 'nonexistent' was not found in config:\/\/config.yml/", $tester->getDisplay());
    }

    public function testDefaultFile()
    {
        $app = $this->getApp();
        $filesystem = new Filesystem(new Local(PHPUNIT_ROOT . '/resources/'));
        $app['filesystem']->mountFilesystem('config', $filesystem);

        $command = new ConfigGet($app);
        $tester = new CommandTester($command);
        $tester->execute(['key' => 'sitename']);
        $this->assertRegExp('/sitename: A sample site/', $tester->getDisplay());
    }

    public function setUp()
    {
        @mkdir(PHPUNIT_ROOT . '/resources/', 0777, true);
        @mkdir(TEST_ROOT . '/app/cache/', 0777, true);
        $distname = realpath(TEST_ROOT . '/app/config/config.yml.dist');
        @copy($distname, PHPUNIT_ROOT . '/resources/config.yml');
    }

    public function tearDown()
    {
        @unlink(PHPUNIT_ROOT . '/resources/config.yml');
        @unlink(TEST_ROOT . '/app/cache/');
    }
}
