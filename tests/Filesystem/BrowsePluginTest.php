<?php
namespace Bolt\Tests\Filesystem;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\BrowsePlugin;
use League\Flysystem\Filesystem;
use League\Flysystem\Cache\Memory;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Adapter\Local;

/**
 * Class to test src/Filesystem/BrowsePlugin.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BrowsePluginTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        
        $adapter = new Local(TEST_ROOT);
        $cache = new Memory();
        $fs = new Filesystem($adapter, $cache);
        
        $plugin = new BrowsePlugin();
        $plugin->setFilesystem($fs);
        $result = $plugin->handle("/", $app);
        $this->assertGreaterThan(0, count($result));
    }
    
    public function testName()
    {
        $plugin = new BrowsePlugin();
        $this->assertEquals('browse', $plugin->getMethod());
    }
    
    public function testImageCalculation()
    {
        $app = $this->getApp();
        $adapter = new Local(TEST_ROOT."/tests/resources");
        $cache = new Memory();
        $fs = new Filesystem($adapter, $cache);
        
        $plugin = new BrowsePlugin();
        $plugin->setFilesystem($fs);
        $result = $plugin->handle("", $app);
        $files = $result[0];
        foreach($files as $file) {
            if($file['type']=='png') {
                $this->assertNotEmpty($file['imagesize']);
            }
        }
    }
    
 
   
}