<?php
namespace Bolt\Tests\Extensions;

use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\Manager;
use League\Flysystem\Adapter\NullAdapter;

/**
 * Class to test src/Filesystem/Manager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ManagerTest extends BoltUnitTest
{

    public function testSetup()
    {
        $manager = $this->getManager();

        $this->assertNotEmpty($manager->getManager('config'));
        $this->assertNotEmpty($manager->getManager());

        $manager->setManager('mytest', $manager->getManager());
        $this->assertNotEmpty($manager->getManager('mytest'));

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
        $fs = $this->getMock('League\Flysystem\Filesystem', array('handle'), array($adapter));

        $manager->setManager('default', $fs);

        $plugin = $this->getMock('League\Flysystem\PluginInterface', array('handle','getMethod','setFilesystem'));

        $plugin->expects($this->once())
            ->method('handle')
            ->will($this->returnValue('success'));

        $plugin->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('testing'));

        $manager->getManager()->addPlugin($plugin);

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
