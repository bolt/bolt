<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Tests\Controller\ControllerUnitTest;
use Silex\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of Upload Controller.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/

class UploadTest extends ControllerUnitTest
{
    public function setup()
    {
        @mkdir(PHPUNIT_ROOT . '/resources/files', 0777, true);
        chmod(PHPUNIT_ROOT . '/resources/files', 0777);
    }

    public function tearDown()
    {
        @unlink(TEST_ROOT . '/app/cache/config_cache.php');
    }

    public function testResponses()
    {
        $this->setRequest(Request::create(
            '/upload/files',
            'POST',
            [],
            [],
            [],
            []
        ));

        $response = $this->controller()->uploadNamspace($this->getRequest(), 'files');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // We haven't posted a file so an empty resultset should be returned
        $content = json_decode($response->getContent());
        $this->assertEquals(0, count($content));
    }

    public function testUpload()
    {
        $request = $this->getFileRequest();
        $response = $this->controller()->uploadNamspace($request, 'files');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent());
        $this->assertEquals(1, count($content));
    }

    public function testInvalidFiletype()
    {
        $this->setRequest(Request::create(
            '/upload/files',
            'POST',
            [],
            [],
            [
                'files' => [
                    [
                        'tmp_name' => PHPUNIT_ROOT . '/resources/generic-logo-evil.exe',
                        'name'     => 'logo.exe'
                    ]
                ]
            ],
            []
        ));

        $response = $this->controller()->uploadNamspace($this->getRequest(), 'files');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent());
        $file = $content[0];
        $this->assertAttributeNotEmpty('error', $file);
        $this->assertRegExp('/extension/i', $file->error);
    }

    public function testBadDefaultLocation()
    {
        $this->getService('resources')->setPath('files', '/path/to/nowhere');
        $this->getFileRequest();

        $this->setExpectedException('RuntimeException', 'Unable to write to upload destination');

        $this->controller()->uploadNamspace($this->getRequest(), 'files');
    }

    public function testHandlerParsing()
    {
        $this->setRequest(Request::create(
            '/upload/files',
            'POST',
            ['handler' => 'files://'],
            [],
            [
                'files' => [
                    [
                        'tmp_name' => PHPUNIT_ROOT . '/resources/generic-logo.png',
                        'name'     => 'logo.png'
                    ]
                ]
            ],
            []
        ));

        $response = $this->controller()->uploadNamspace($this->getRequest(), 'files');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMultipleHandlerParsing()
    {
        $this->setRequest(Request::create(
            '/upload/files',
            'POST',
            ['handler' => ['files://', 'ftp://']],
            [],
            [
                'files' => [
                    [
                        'tmp_name' => __DIR__ . '/resources/generic-logo.png',
                        'name'     => 'logo.png'
                    ]
                ]
            ],
            []
        ));

        // Not properly implemented as yet, this will need to be revisited on implementation
        $this->setExpectedException('League\Flysystem\FileNotFoundException', 'File not found at path: logo.png');
        $this->controller()->uploadNamspace($this->getRequest(), 'files');
    }

    public function testFileObjectUploads()
    {
        $this->setRequest(Request::create(
            '/upload/files',
            'POST',
            [],
            [],
            [
                'files' => [new UploadedFile(PHPUNIT_ROOT . '/resources/generic-logo.png', 'logo.png')]
            ],
            []
        ));
        $response = $this->controller()->uploadNamspace($this->getRequest(), 'files');

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    protected function getFileRequest($namespace = 'files')
    {
        $this->setRequest(Request::create(
            '/upload/' . $namespace,
            'POST',
            [],
            [],
            [
                'files' => [
                    [
                        'tmp_name' => PHPUNIT_ROOT . '/resources/generic-logo.png',
                        'name'     => 'logo.png'
                    ]
                ]
            ],
            []
        ));

        return $this->getRequest();
    }

//     protected function getApp()
//     {
//         $bolt = parent::getApp();

//         return $this->authApp($bolt);
//     }

//     protected function authApp(Application $bolt)
//     {
//         $users = $this->getMock('Bolt\Users', ['isValidSession', 'isAllowed'], [$bolt]);
//         $users->expects($this->any())
//             ->method('isValidSession')
//             ->will($this->returnValue(true));

//         $users->expects($this->any())
//             ->method('isAllowed')
//             ->will($this->returnValue(true));

//         $bolt['users'] = $users;

//         return $bolt;
//     }

    /**
     * @return \Bolt\Controller\Backend\Upload
     */
    protected function controller()
    {
        return $this->getService('controller.backend.upload');
    }
}
