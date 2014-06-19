<?php
namespace Bolt\Tests;
use Bolt\Application;
use Bolt\Configuration\ResourceManager;
use Bolt\Configuration\Composer;
use Symfony\Component\HttpFoundation\Request;


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
        $this->assertEquals("cli://bolt.dev/",      $config->getUrl("rooturl"));
    }
    
    public function testCustomRequest()
    {
        $request = Request::create(
            "/bolt/test/location",
            "GET",
            array(),
            array(),
            array(),
            array(
                'HTTP_HOST'=>'test.dev',
                'SERVER_PROTOCOL'=>'https'
            )  
        );
        $config = new ResourceManager(__DIR__, $request);
        $app = new Application(array('resources'=>$config));
        $this->assertEquals("https",                $config->getRequest("protocol"));
        $this->assertEquals("test.dev",             $config->getRequest("hostname"));
        $this->assertEquals("https://bolt.dev/bolt/test/location",  $config->getUrl("canonical"));
    }
    
    public function testComposerCustomConfig()
    {
        $config = new Composer(__DIR__);
        $config->setPath('cache', 'app/cache');
        $config->setPath('database', 'app/database');
        $app = new Application(array('resources'=>$config));
        $this->assertEquals(__DIR__."/vendor/bolt/bolt/app",            $config->getPath("app"));
        $this->assertEquals(__DIR__."/vendor/bolt/bolt/app/extensions", $config->getPath("extensions"));
        $this->assertEquals("/bolt-public/",                            $config->getUrl("app"));
    }
    
    public function testNonRootDirectory()
    {
        $request = Request::create(
            "/sub/directory/bolt/test/location",
            "GET",
            array(),
            array(),
            array(),
            array(
                'SCRIPT_NAME'       => '/sub/directory/index.php',
                'PHP_SELF'          => '/sub/directory/index.php',
                'SCRIPT_FILENAME'   => '/path/to/sub/directory/index.php'
            )  
        );
                
        $config = new ResourceManager(__DIR__, $request);
        $app = new Application(array('resources'=>$config));
        $this->assertEquals('/sub/directory/',                  $config->getUrl('root'));
        $this->assertEquals('/sub/directory/app/',              $config->getUrl('app'));
        $this->assertEquals('/sub/directory/app/extensions/',   $config->getUrl('extensions'));
        $this->assertEquals('/sub/directory/files/',            $config->getUrl('files'));
        $this->assertEquals('/sub/directory/async/',            $config->getUrl('async'));
        $this->assertContains('/sub/directory/theme/',          $config->getUrl('theme'));
    }
    
    public function testConfigDrivenUrls()
    {
        $config = new ResourceManager(__DIR__);
        $app = new Application(array('resources'=>$config));
        $this->assertEquals('/bolt/',  $config->getUrl('bolt'));
        $this->assertEquals('/bolt/files/files/', $app['config']->get('general/wysiwyg/filebrowser/imageBrowseUrl'));
    }
    
    public function testConfigDrivenUrlsWithBrandingOverride()
    {
        $config = new ResourceManager(__DIR__);
        $app = new Application(array('resources'=>$config));
        $app['config']->set('general/branding/path', '/custom');
        $config->initialize();
        $this->assertEquals('/custom/',  $config->getUrl('bolt'));
        $this->assertEquals('/custom/files/files/', $app['config']->get('general/wysiwyg/filebrowser/imageBrowseUrl'));
    }
    
    public function testConfigsWithNonRootDirectory()
    {
        $request = Request::create(
            "/sub/directory/bolt/test/location",
            "GET",
            array(),
            array(),
            array(),
            array(
                'SCRIPT_NAME'       => '/sub/directory/index.php',
                'PHP_SELF'          => '/sub/directory/index.php',
                'SCRIPT_FILENAME'   => '/path/to/sub/directory/index.php'
            )  
        );
        
        $config = new ResourceManager(__DIR__, $request);
        $app = new Application(array('resources'=>$config));
        $app['config']->set('general/branding/path', '/custom');
        $config->initialize();
        $this->assertEquals('/sub/directory/custom/',  $config->getUrl('bolt'));
        $this->assertEquals(
            '/sub/directory/custom/files/files/', 
            $app['config']->get('general/wysiwyg/filebrowser/imageBrowseUrl')
        );
    }


}