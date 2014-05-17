<?php
namespace Bolt\Tests;
use Bolt\Application;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\ComposerResources;

/**
 * Class to test correct operation and locations of resource manager class and extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class ResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    
    
    public function setup()
    {
        
    }
    
    public function testConstruction()
    {
        $config = new ResourceManager(__DIR__);
        $this->assertEquals(\PHPUnit_Framework_Assert::readAttribute($config, 'root'), __DIR__);
    }
    
    public function testDefaultPaths()
    {
        $config = new ResourceManager(__DIR__);
        $this->assertEquals(__DIR__,                    $config->getPath("rootpath"));
        $this->assertEquals(__DIR__."/app",             $config->getPath("apppath"));
        $this->assertEquals(__DIR__."/app/extensions",  $config->getPath("extensionspath"));
        $this->assertEquals(__DIR__."/files",           $config->getPath("filespath"));
        $this->assertEquals(__DIR__,                    $config->getPath("web"));
        $this->assertEquals(__DIR__."/app/cache",       $config->getPath("cache"));
        $this->assertEquals(__DIR__."/app/config",      $config->getPath("config"));
    }
    
    public function testShorAliasedPaths()
    {
        $config = new ResourceManager(__DIR__);
        $this->assertEquals(__DIR__,            $config->getPath("root"));
        $this->assertEquals(__DIR__."/app",     $config->getPath("app"));
        $this->assertEquals(__DIR__."/files",   $config->getPath("files"));
    }
    
    public function testDefaultUrls()
    {
        $config = new ResourceManager(__DIR__);
        $this->assertEquals("/",                $config->getUrl("root"));
        $this->assertEquals("/app/",            $config->getUrl("app"));
        $this->assertEquals("/app/extensions/", $config->getUrl("extensions"));
        $this->assertEquals("/async/",          $config->getUrl("async"));
        $this->assertEquals("/bolt/",           $config->getUrl("bolt"));
        $this->assertEquals("/files/",          $config->getUrl("files"));
    }
    
    public function testBoltAppSetup()
    {
        $config = new ResourceManager(__DIR__);
        $app = new Application(array('resources'=>$config));
        $this->assertEquals($config->getPaths(), $app['resources']->getPaths());
        
        // Test that the Application has initialised the resources, injecting in config values.
        $this->assertContains(__DIR__."/theme",      $config->getPath("theme"));
        $this->assertNotEmpty($config->getUrl("canonical"));
    }
    
    public function testDefaultRequest()
    {
        $config = new ResourceManager(__DIR__);
        $app = new Application(array('resources'=>$config));
        $this->assertEquals("cli",                  $config->getRequest("protocol"));
        $this->assertEquals("bolt.dev",             $config->getRequest("hostname"));
        $this->assertEquals("cli://bolt.dev/bolt",  $config->getUrl("canonical"));
        $this->assertEquals("cli://bolt.dev",       $config->getUrl("host"));
        $this->assertEquals("cli://bolt.dev/",      $config->getUrl("root"));
    }
    
    
}