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
        $uploadfolder = mkdir(__DIR__."/tmpupload");
    }
    
    public function tearDown()
    {
        rmdir(__DIR__."/tmpupload");
    }
    
    public function testResponses()
    {
        global $app;
        $app = $this->getApp();
        
        $request = Request::create(
            "/upload/files",
            "POST",
            array(),
            array(),
            array(),
            array()  
        );

        $response = $app->handle($request);
        print_r($response->__toString()); exit;
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