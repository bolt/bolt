<?php
namespace Bolt\Tests\Filesystem;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\ThumbnailUrlPlugin;
use Bolt\Filesystem\Manager;
use League\Flysystem\Filesystem;
use League\Flysystem\Cache\Memory;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Adapter\Local;

/**
 * Class to test src/Filesystem/SearchPlugin.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ThumbnailUrlPluginTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        
        $adapter = new Local(TEST_ROOT."/tests/resources");
        $cache = new Memory();
        $fs = new Filesystem($adapter, $cache);
        
        $manager = new Manager($app);
        $manager->setManager('files', $fs);
        $manager->addPlugin(new ThumbnailUrlPlugin($app));

        
        $result = $fs->thumb('generic-logo.png', 200, 200, 'crop');
        $this->assertEquals('/thumbs/200x200c/generic-logo.png', $result);
    }
    
    public function testName()
    {
        $app = $this->getApp();
        $plugin = new ThumbnailUrlPlugin($app);
        $this->assertEquals('thumb', $plugin->getMethod());
    }
    
    
    
 
   
}