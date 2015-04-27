<?php
namespace Bolt\Tests\Controller\Backend;

use Bolt\Controller\Backend\FileManager;
use Bolt\Tests\BoltUnitTest;
use League\Flysystem\File;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation of src/Controller/Backend/Log.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class FileManagerTest extends BoltUnitTest
{
    public function testEdit()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.file_manager'];

        $app['request'] = $request = Request::create('/bolt/file/edit/config/config.yml');
        $response = $controller->actionEdit($request, 'config', 'config.yml');
        $this->assertEquals('editfile/editfile.twig', $response->getTemplateName());
    }

    public function testManage()
    {
        $app = $this->getApp();
        $controller = $app['controller.backend.file_manager'];

        $this->removeCSRF($app);
        $app['request'] = $request = Request::create('/bolt/files');
        $response = $controller->actionManage($request, 'files', '');

        $context = $response->getContext();
        $this->assertEquals('', $context['context']['path']);
        $this->assertEquals('files', $context['context']['namespace']);
        $this->assertEquals(array(), $context['context']['files']);

        // Try and upload a file
        $perms = $this->getMock('Bolt\Filesystem\FilePermissions', array('allowedUpload'), array($app));
        $perms->expects($this->any())
            ->method('allowedUpload')
            ->will($this->returnValue(true));
        $app['filepermissions'] = $perms;

        $app['request'] = $request = Request::create(
            '/upload/files',
            'POST',
            array(),
            array(),
            array(
                'form' => array(
                    'FileUpload' => array(
                        new UploadedFile(
                            PHPUNIT_ROOT . '/resources/generic-logo-evil.exe',
                            'logo.exe'
                        )
                    ),
                    '_token'     => 'xyz'
                )
            )
        );

        $response = $controller->actionManage($request, 'files', '');
    }

    protected function removeCSRF($app)
    {
        // Symfony forms need a CSRF token so we have to mock this too
        $csrf = $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider', array('isCsrfTokenValid', 'generateCsrfToken'), array('form'));
        $csrf->expects($this->any())
            ->method('isCsrfTokenValid')
            ->will($this->returnValue(true));

        $csrf->expects($this->any())
            ->method('generateCsrfToken')
            ->will($this->returnValue('xyz'));

        $app['form.csrf_provider'] = $csrf;
    }
}
