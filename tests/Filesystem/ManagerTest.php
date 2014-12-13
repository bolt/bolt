<?php
namespace Bolt\Tests\Extensions;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\Manager;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;

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
        $app = $this->getApp();
        $app['resources']->setPath('files', __DIR__);
        
        $manager = new Manager($app);
        
        $this->assertNotEmpty($manager->getManager('config'));
        $this->assertNotEmpty($manager->getManager());
        
        $manager->setManager('mytest', $manager->getManager());
        $this->assertNotEmpty($manager->getManager('mytest'));

    }
    
    public function testBadMountFails()
    {
        $app = $this->getApp();        
        $manager = new Manager($app);
        $mount = $manager->mount('fails', "/baddir");
        $this->assertFalse($mount);
    }
    
    public function testManagerForwardsToDefault()
    {
        $app = $this->getApp();
        $app['resources']->setPath('files', __DIR__);
        $manager = new Manager($app);
        
        $adapter = new NullAdapter();
        $fs = $this->getMock(Filesystem::class, array('handle'),array($adapter));
        
        $manager->setManager('default', $fs);
        
        $plugin = $this->getMock(PluginInterface::class, array('handle','getMethod','setFilesystem'));
                
        $plugin->expects($this->once())
            ->method('handle')
            ->will($this->returnValue('success'));
        $plugin->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('test'));
        
        $manager->addPlugin($plugin);
        $response = $manager->test('test');
        
        $this->assertEquals('success', $response);
        
    }
    
    public function testPlugins()
    {
        $app = $this->getApp();
        $app['resources']->setPath('files', __DIR__);
        $manager = new Manager($app);
        $this->assertEquals('/files/findfile', $manager->url('findfile'));
    }
    

 
   
}