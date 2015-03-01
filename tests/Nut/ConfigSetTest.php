<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\ConfigSet;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ConfigSet.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ConfigSetTest extends BoltUnitTest
{
    public function testSet()
    {
        $app = $this->getApp();
        $app['filesystem']->mount('config', __DIR__ . '/resources/');

        $command = new ConfigSet($app);
        $tester = new CommandTester($command);

        // Test successful update
        $tester->execute(array('key' => 'sitename', 'value' => 'my test', '--file' => 'config.yml'));
        $this->assertRegexp("/New value for sitename: my test was successful/", $tester->getDisplay());

        // Test non-existent fails
        $tester->execute(array('key' => 'nonexistent', 'value' => 'test', '--file' => 'config.yml'));
        $this->assertEquals("The key 'nonexistent' was not found in config.yml.\n", $tester->getDisplay());
    }

    public function testDefaultFile()
    {
        $app = $this->getApp();
        $command = new ConfigSet($app);
        $tester = new CommandTester($command);
        $app['resources']->setPath('config', __DIR__ . '/resources');
        $tester->execute(array('key' => 'nonexistent', 'value' => 'test'));
        $this->assertEquals("The key 'nonexistent' was not found in config.yml.\n", $tester->getDisplay());
    }

    public static function setUpBeforeClass()
    {
        @mkdir(__DIR__ . '/resources/', 0777, true);
        @mkdir(__DIR__ . '/../../app/cache/', 0777, true);
        $distname = realpath(__DIR__ . '/../../app/config/config.yml.dist');
        @copy($distname, __DIR__ . '/resources/config.yml');
    }

    public static function tearDownAfterClass()
    {
        @unlink(__DIR__ . '/resources/config.yml');
        @unlink(__DIR__ . '/../../app/cache/');
    }
}
