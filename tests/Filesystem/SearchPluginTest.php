<?php
namespace Bolt\Tests\Filesystem;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\SearchPlugin;
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
class SearchPluginTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        
        $adapter = new Local(TEST_ROOT."/tests/resources");
        $cache = new Memory();
        $fs = new Filesystem($adapter, $cache);
        
        $plugin = new SearchPlugin();
        $plugin->setFilesystem($fs);
        $result = $plugin->handle("*");
        $this->assertGreaterThan(0, count($result));
    }
    
    public function testName()
    {
        $plugin = new SearchPlugin();
        $this->assertEquals('search', $plugin->getMethod());
    }
    
    
    
 
   
}