<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Async/FileManager.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class FilesystemManagerTest extends ControllerUnitTest
{
    const FOLDER_NAME = '__phpunit_test_folder_delete_me';

    public function testBrowse()
    {
        $this->setRequest(Request::create('/async/browse'));
        $response = $this->controller()->browse($this->getRequest(), 'files', '/');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('@bolt/async/browse.twig', $response->getTemplateName());
    }

    public function testCreateFolder()
    {
        $this->setRequest(Request::create('/async/folder/create', 'POST', [
            'namespace'  => 'files',
            'parent'     => '',
            'foldername' => '' . self::FOLDER_NAME,
        ]));
        $response = $this->controller()->createFolder($this->getRequest());

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeleteFile()
    {
        //         $this->setRequest(Request::create('/async/file/delete', 'POST', [
//             'namespace' => 'files',
//             'filename'  => 'foo.txt',
//         ]));
//         $response = $this->controller()->deleteFile($this->getRequest());

//         $this->assertTrue($response);
    }

    public function testDuplicateFile()
    {
        //         $this->setRequest(Request::create('/async/file/duplicate', 'POST', [
//             'namespace' => 'files',
//             'filename'  => 'foo.txt',
//         ]));
//         $response = $this->controller()->duplicateFile($this->getRequest());

//         $this->assertTrue($response);
    }

    public function testFileBrowser()
    {
        //$this->setSessionUser(new Entity\Users($this->getService('users')->getUser('admin')));
        $this->setRequest(Request::create('/async/recordbrowser'));

        $response = $this->controller()->recordBrowser();

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/recordbrowser/recordbrowser.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFilesAutoComplete()
    {
        $fs = new Filesystem();
        $fs->mirror(TEST_ROOT . '/files', PHPUNIT_WEBROOT . '/files');

        $this->setRequest(Request::create('/async/file/autocomplete', 'GET', [
            'term' => 'blu',
        ]));

        $response = $this->controller()->filesAutoComplete($this->getRequest());
        $fs->remove(PHPUNIT_WEBROOT . '/files');

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertRegExp('/blur-breakfast-coffee-271.jpg/', (string) $response);
        $this->assertRegExp('/blur-flowers-home-1093.jpg/', (string) $response);
    }

    public function testRemoveFolder()
    {
        $this->setRequest(Request::create('/async/folder/delete', 'POST', [
            'namespace'  => 'files',
            'parent'     => '',
            'foldername' => self::FOLDER_NAME,
        ]));
        $this->controller()->createFolder($this->getRequest());
        $response = $this->controller()->removeFolder($this->getRequest());

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRenameFile()
    {
        //         $this->setRequest(Request::create('/async/file/rename', 'POST', [
//             'namespace' => 'files',
//             'parent'    => '',
//             'oldname'   => 'foo.txt',
//             'newname'   => 'bar.txt',
//         ]));
//         $response = $this->controller()->renameFile($this->getRequest());

//         $this->assertTrue($response);
    }

    public function testRenameFolder()
    {
        //         $this->setRequest(Request::create('/async/folder/rename', 'POST', [
//             'namespace' => 'files',
//             'parent'    => '',
//             'oldname'   => 'foo',
//             'newname'   => 'bar',
//         ]));
//         $response = $this->controller()->renameFolder($this->getRequest());

//         $this->assertTrue($response);
    }

    /**
     * @return \Bolt\Controller\Async\FilesystemManager
     */
    protected function controller()
    {
        return $this->getService('controller.async.filesystem_manager');
    }
}
