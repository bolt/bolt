<?php
namespace Bolt\Tests\Filesystem;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\FlysystemContainer;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Adapter\Local;

/**
 * Class to test src/Filesystem/FilesystemContainer.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class FlysystemContainerTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        
        $adapter = new NullAdapter(TEST_ROOT);
        $fs = new Filesystem($adapter);
        
        $container = new FlysystemContainer($fs);
        
        $this->assertTrue($container->isWritable());
        $this->assertFalse($container->has('nonexistent'));
        $this->assertTrue($container->save('filename', 'content'));
        $this->assertFalse($container->delete('filename'));
        $this->assertEquals('destination', $container->moveUploadedFile(__FILE__, 'destination'));
        $this->assertFalse($container->moveUploadedFile('/dev/null', 'destination'));
    }
    

 
   
}