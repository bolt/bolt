<?php

namespace Bolt\Tests\Controller\Backend;

use Bolt\Filesystem\FilePermissions;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Response\TemplateResponse;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Backend/Log.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class FileManagerTest extends ControllerUnitTest
{
    public function testEdit()
    {
        $this->setRequest(Request::create('/bolt/file/edit/config/config.yml'));

        /** @var TemplateResponse $response */
        $response = $this->controller()->edit($this->getRequest(), 'config', 'config.yml');

        $this->assertEquals('@bolt/editfile/editfile.twig', $response->getTemplate());
    }

    public function testManage()
    {
        $app = $this->getApp();
        $this->removeCSRF($app);
        $this->setRequest(Request::create('/bolt/files'));

        $response = $this->controller()->manage($this->getRequest(), 'files', '');
        $context = $response->getContext()->get('context');

        /** @var DirectoryInterface $dir */
        $dir = $context['directory'];
        $this->assertTrue($dir instanceof DirectoryInterface);
        $this->assertEquals('files://', $dir->getFullPath());
        $this->assertEquals([], $context['files']);

        // Try and upload a file
        $perms = $this->getMockBuilder(FilePermissions::class)
            ->setMethods(['allowedUpload'])
            ->setConstructorArgs([$app['config']])
            ->getMock()
        ;
        $perms->expects($this->any())
            ->method('allowedUpload')
            ->will($this->returnValue(true));
        $this->setService('filepermissions', $perms);

        $this->setRequest(Request::create(
            '/upload/files',
            'POST',
            [],
            [],
            [
                'form' => [
                    'FileUpload' => [
                        new UploadedFile(
                            PHPUNIT_ROOT . '/resources/generic-logo-evil.exe',
                            'logo.exe'
                        ),
                    ],
                    '_token'     => 'xyz',
                ],
            ]
        ));

        $this->controller()->manage($this->getRequest(), 'files', '');
    }

    /**
     * @return \Bolt\Controller\Backend\FileManager
     */
    protected function controller()
    {
        return $this->getService('controller.backend.file_manager');
    }
}
