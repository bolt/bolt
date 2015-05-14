<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Response\BoltResponse;
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
        $response = $this->controller()->actionBrowse($this->getRequest(), 'files', '/');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('files_async/files_async.twig', $response->getTemplateName());
    }

    public function testCreateFolder()
    {
        $this->setRequest(Request::create('/async/createfolder', 'POST', array(
            'namespace'  => 'files',
            'parent'     => '',
            'foldername' => '__phpunit_test_delete_me',
        )));
        $response = $this->controller()->actionCreateFolder($this->getRequest());

        $this->assertTrue($response);
    }

    public function testDeleteFile()
    {
        //         $this->setRequest(Request::create('/async/deletefile', 'POST', array(
//             'namespace' => 'files',
//             'filename'  => 'foo.txt',
//         )));
//         $response = $this->controller()->actionDeleteFile($this->getRequest());

//         $this->assertTrue($response);
    }

    public function testDuplicateFile()
    {
        //         $this->setRequest(Request::create('/async/duplicatefile', 'POST', array(
//             'namespace' => 'files',
//             'filename'  => 'foo.txt',
//         )));
//         $response = $this->controller()->actionDuplicateFile($this->getRequest());

//         $this->assertTrue($response);
    }

    public function testFileBrowser()
    {
        //$this->getService('users')->currentuser = $this->getService('users')->getUser('admin');
        $this->setRequest(Request::create('/async/filebrowser'));

        $response = $this->controller()->actionRecordBrowser('pages');

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('recordbrowser/recordbrowser.twig', $response->getTemplateName());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFilesAutoComplete()
    {
        $this->setRequest(Request::create('/async/filesautocomplete', 'GET', array(
            'term' => '*',
        )));

        $response = $this->controller()->actionFilesAutoComplete($this->getRequest());

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRemoveFolder()
    {
        $this->setRequest(Request::create('/async/removefolder', 'POST', array(
            'namespace'  => 'files',
            'parent'     => '',
            'foldername' => '__phpunit_test_delete_me',
        )));
        $response = $this->controller()->actionRemoveFolder($this->getRequest());

        $this->assertTrue($response);
    }

    public function testRenameFile()
    {
        //         $this->setRequest(Request::create('/async/renamefile', 'POST', array(
//             'namespace' => 'files',
//             'parent'    => '',
//             'oldname'   => 'foo.txt',
//             'newname'   => 'bar.txt',
//         )));
//         $response = $this->controller()->actionRenameFile($this->getRequest());

//         $this->assertTrue($response);
    }

    public function testRenameFolder()
    {
        //         $this->setRequest(Request::create('/async/renamefolder', 'POST', array(
//             'namespace' => 'files',
//             'parent'    => '',
//             'oldname'   => 'foo',
//             'newname'   => 'bar',
//         )));
//         $response = $this->controller()->actionRenameFolder($this->getRequest());

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
