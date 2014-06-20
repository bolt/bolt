<?php
namespace Bolt\Tests;
use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
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
        $config = new Config\ResourceManager(__DIR__);
        $app = new Application(array('resources'=>$config));
        $app['debug'] = false;
        $app->initialize();
        
        $request = Request::create(
            "/upload/",
            "GET",
            array(),
            array(),
            array(),
            array()  
        );

        $app->run($request);
    }
    


}