<?php
namespace Bolt\Tests\Extensions;

use Bolt\Filesystem\Manager;
use Bolt\Tests\BoltUnitTest;
use League\Flysystem\Adapter\NullAdapter;

/**
 * Class to test src/Filesystem/Manager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ManagerTest extends BoltUnitTest
{
    public function testSetup()
    {
        $manager = $this->getManager();

        $this->assertNotEmpty($manager->getFilesystem('config'));
        $this->assertNotEmpty($manager->getFilesystem());

        $manager->mountFilesystem('mytest', $manager->getFilesystem());
        $this->assertNotEmpty($manager->getFilesystem('mytest'));
    }

    public function testBadMountUsesNullAdapter()
    {
        $manager = $this->getManager();

        $manager->mount('fails', '/baddir');
        $adapter = $manager->getAdapter('fails://');
        $this->assertInstanceOf('League\Flysystem\Adapter\NullAdapter', $adapter);
    }

    public function testManagerForwardsToDefault()
    {
        $manager = $this->getManager();

        $adapter = new NullAdapter();
        $fs = $this->getMock('League\Flysystem\Filesystem', ['handle'], [$adapter]);

        $manager->mountFilesystem('default', $fs);

        $plugin = $this->getMock('League\Flysystem\PluginInterface', ['handle', 'getMethod', 'setFilesystem']);

        $plugin->expects($this->once())
            ->method('handle')
            ->will($this->returnValue('success'));

        $plugin->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('testing'));

        $manager->addPlugin($plugin);

        $response = $manager->testing('arg');

        $this->assertEquals('success', $response);
    }

    public function testPlugins()
    {
        $manager = $this->getManager();
        $this->assertEquals('/files/findfile', $manager->url('findfile'));
    }

    protected function getManager()
    {
        $app = $this->getApp();
        $app['resources']->setPath('files', __DIR__);

        return new Manager($app);
    }
}
