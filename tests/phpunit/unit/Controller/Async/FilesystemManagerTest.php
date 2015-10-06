<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
use Bolt\Storage\Entity;
use Bolt\Tests\Controller\ControllerUnitTest;
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
    public function testBrowse()
    {
        $this->setRequest(Request::create('/async/browse'));
        $response = $this->controller()->browse($this->getRequest(), 'files', '/');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('@bolt/files_async/files_async.twig', $response->getTemplateName());
    }

    public function testCreateFolder()
    {
        $this->setRequest(Request::create('/async/createfolder', 'POST', [
            'namespace'  => 'files',
            'parent'     => '',
            'foldername' => '__phpunit_test_delete_me',
        ]));
        $response = $this->controller()->createFolder($this->getRequest());

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
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
        $this->setRequest(Request::create('/async/filebrowser'));

        $response = $this->controller()->recordBrowser();

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('@bolt/recordbrowser/recordbrowser.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFilesAutoComplete()
    {
        $this->setRequest(Request::create('/async/file/autocomplete', 'GET', [
            'term' => '*',
        ]));

        $response = $this->controller()->filesAutoComplete($this->getRequest());

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRemoveFolder()
    {
        $this->setRequest(Request::create('/async/removefolder', 'POST', [
            'namespace'  => 'files',
            'parent'     => '',
            'foldername' => '__phpunit_test_delete_me',
        ]));
        $response = $this->controller()->removeFolder($this->getRequest());

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
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
        //         $this->setRequest(Request::create('/async/renamefolder', 'POST', [
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
