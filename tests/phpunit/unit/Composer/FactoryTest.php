<?php
namespace Bolt\Tests\Composer;

use Bolt\Composer\Action\RemovePackage;
use Bolt\Composer\Factory;
use Bolt\Composer\PackageManager;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Composer/Factory.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FactoryTest extends BoltUnitTest
{
    public function testConstruct()
    {
        $app = $this->getApp();
        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $this->assertArrayHasKey('basedir', \PHPUnit_Framework_Assert::readAttribute($factory, 'options'));
    }

    public function testGetComposer()
    {
        $app = $this->getApp();
        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $composer = $factory->getComposer();
        $this->assertInstanceOf('Composer\Composer', $composer);
    }

    public function testGetIo()
    {
        $app = $this->getApp();
        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $io = $factory->getIO();
        $this->assertInstanceOf('Composer\IO\BufferIO', $io);
    }

    public function testResetComposer()
    {
        $app = $this->getApp();
        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $composer = $factory->resetComposer();
        $this->assertInstanceOf('Composer\Composer', $composer);
    }

    public function testGetOutput()
    {
        $app = $this->getApp();
        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $io = $factory->getIO();
        $output = $factory->getOutput();
        $this->assertEquals('', $output);
    }

    public function testFindVersion()
    {
        $app = $this->getApp();

        $manager = new PackageManager($app);
        $manager->requirePackage(['name' => 'gawain/clippy', 'version' => '~2']);

        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $version = $factory->findBestVersionForPackage('gawain/clippy');
        $this->assertRegExp('#^.*#', $version['requirever']);

        $this->assertNull($factory->findBestVersionForPackage('bolt/bolt'));
    }

    public function testSSLDowngrade()
    {
        $app = $this->getApp();
        $factory = new Factory($app, ['basedir' => TEST_ROOT . '/extensions']);
        $factory->downgradeSsl = true;
        $composer = $factory->getComposer();
        $repos = $composer->getRepositoryManager()->getRepositories();
    }

    public function tearDown()
    {
        $app = $this->getApp();
        $action = new RemovePackage($app);
        $action->execute(['gawain/clippy']);
    }
}
