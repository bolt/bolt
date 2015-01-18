<?php
namespace Bolt\Tests\Controller;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test correct operation of Upload Controller.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class UploadControllerTest extends BoltUnitTest
{

    public function setup()
    {
        @mkdir(TEST_ROOT . '/tests/resources/files', 0777, true);
        chmod(TEST_ROOT . '/tests/resources/files', 0777);
    }

    public function tearDown()
    {
        @unlink(TEST_ROOT . '/app/cache/config_cache.php');
    }



    public function testResponses()
    {
        $app = $this->getApp();

        $request = Request::create(
            '/upload/files',
            'POST',
            array(),
            array(),
            array(),
            array()
        );

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        // We haven't posted a file so an empty resultset should be returned
        $content = json_decode($response->getContent());
        $this->assertEquals(0, count($content));
    }
 
    public function testUpload()
    {
        $app = $this->getApp();
        $request = $this->getFileRequest();
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent());
        $this->assertEquals(1, count($content));
    }


    public function testInvalidFiletype()
    {
        $app = $this->getApp();
        $request = Request::create(
            '/upload/files',
            'POST',
            array(),
            array(),
            array(
                'files' => array(
                    array(
                        'tmp_name' => TEST_ROOT . '/tests/resources/generic-logo-evil.exe',
                        'name' => 'logo.exe'
                    )
                )
            ),
            array()
        );

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent());
        $file = $content[0];
        $this->assertAttributeNotEmpty('error', $file);
        $this->assertRegExp('/extension/i', $file->error);
    }
    
    public function testBadDefaultLocation()
    {
        $app = $this->makeApp();
        $app['resources']->setPath('files', "/path/to/nowhere");
        $app->initialize();
        $app = $this->authApp($app);
        $request = $this->getFileRequest();
        $response = $app->handle($request);
        $this->assertEquals(500, $response->getStatusCode());
    }
    
    
    public function testHandlerParsing()
    {
        $app = $this->getApp();
        
        $request = Request::create(
            '/upload/files',
            'POST',
            array('handler'=>'files://'),
            array(),
            array(
                'files' => array(
                    array(
                        'tmp_name' => __DIR__ . '/resources/generic-logo.png',
                        'name' => 'logo.png'
                    )
                )
            ),
            array()
        );
                
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        
    }
    
    
    public function testMultipleHandlerParsing()
    {
        $app = $this->getApp();
        
        $request = Request::create(
            '/upload/files',
            'POST',
            array('handler'=>array('files://', 'ftp://')),
            array(),
            array(
                'files' => array(
                    array(
                        'tmp_name' => __DIR__ . '/resources/generic-logo.png',
                        'name' => 'logo.png'
                    )
                )
            ),
            array()
        );
                
        $response = $app->handle($request);
        // Not properly implemented as yet, this will need to be revisited on implementation
        $this->assertEquals(500, $response->getStatusCode());
    }
    
    public function testFileObjectUploads()
    {
        $app = $this->getApp();
        $request = Request::create(
            '/upload/files',
            'POST',
            array(),
            array(),
            array(
                'files' => array(new UploadedFile(TEST_ROOT . '/tests/resources/generic-logo.png', 'logo.png'))
            ),
            array()
        );
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }



    protected function getFileRequest($namespace='files')
    {
        $request = Request::create(
            '/upload/'.$namespace,
            'POST',
            array(),
            array(),
            array(
                'files' => array(
                    array(
                        'tmp_name' => TEST_ROOT . '/tests/resources/generic-logo.png',
                        'name' => 'logo.png'
                    )
                )
            ),
            array()
        );
        return $request;
    }
    
    protected function getApp() {
        $bolt = parent::getApp();  
        return $this->authApp($bolt);
    }
    
    protected function authApp($bolt)
    {
        $users = $this->getMock('Bolt\Users', array('isValidSession', 'isAllowed'), array($bolt));
        $users->expects($this->any())
            ->method('isValidSession')
            ->will($this->returnValue(true));
            
        $users->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
            
        $bolt['users'] = $users;
        
        return $bolt;
    }





}
