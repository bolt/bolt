<?php
namespace Bolt\Tests;
use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
    
use Bolt\Configuration as Config;


/**
 * Class to test correct operation of Upload Controller.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class UploadControllerTest extends \PHPUnit_Framework_TestCase
{
    
    
    public function setup()
    {
        $uploadfolder = mkdir(__DIR__."/files", 0777, true);
    }
    
    public function tearDown()
    {
        rmdir(__DIR__."/files");
    }
    
    
    /**
    * @runInSeparateProcess
    */
    public function testResponses()
    {
        global $app;
        $app = $this->getApp();
        $app['resources']->setPath('files', __DIR__."/files");
        
        $request = Request::create(
            "/upload/files",
            "POST",
            array(),
            array(),
            array(),
            array()  
        );

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent()); 

        // We haven't posted a file so this error should happen by default
        $this->assertEquals("Filetype not allowed", $json[0]->error);
    }
    
    
    protected function getApp()
    {
        $sessionMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
        ->setMethods(array('clear'))
        ->setConstructorArgs(array(new MockFileSessionStorage()))
        ->getMock();
                        
        $config = new Config\ResourceManager(__DIR__);
        $app = new Application(array('resources'=>$config));
        $app['config']->set('general/database', array('databasename'=>'test','username'=>'test'));
        $app['debug'] = false;
        $app['session'] = $sessionMock;
        $app->initialize();
        return $app;
    }
    


}