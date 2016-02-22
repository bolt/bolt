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
    const FILESYSTEM = 'files';

    const FILE_NAME = '__phpunit_test_file_delete_me';
    const FILE_NAME_2 = '__phpunit_test_file_2_delete_me';
    const FOLDER_NAME = '__phpunit_test_folder_delete_me';

    public function testBrowse()
    {
        $this->setRequest(Request::create('/async/browse'));
        $response = $this->controller()->browse($this->getRequest(), self::FILESYSTEM, '/');

        $this->assertInstanceOf(BoltResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('@bolt/async/browse.twig', $response->getTemplateName());
    }

    public function testCreateFolder()
    {
        $this->setRequest(Request::create('/async/folder/create', 'POST', [
            'namespace'  => self::FILESYSTEM,
            'parent'     => '',
            'foldername' => self::FOLDER_NAME
        ]));
        $response = $this->controller()->createFolder($this->getRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Test whether the new folder actually exists
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FOLDER_NAME));
    }

    public function testRemoveFolder()
    {
        $this->setRequest(Request::create('/async/folder/delete', 'POST', [
            'namespace'  => self::FILESYSTEM,
            'parent'     => '',
            'foldername' => self::FOLDER_NAME,
        ]));

        // The folder should exist before deleting
        $this->controller()->createFolder($this->getRequest());
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FOLDER_NAME));

        $response = $this->controller()->removeFolder($this->getRequest());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertFalse($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FOLDER_NAME));
    }

    public function testCreateFile()
    {
        $this->setRequest(Request::create('/async/file/create', 'POST', [
            'namespace'  => self::FILESYSTEM,
            'parentPath' => '',
            'filename'   => self::FILE_NAME
        ]));
        $response = $this->controller()->createFile($this->getRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Test whether the new folder actually exists
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FILE_NAME));
    }

    /**
     * Duplicating a file five times should create FILENAME_copy1-5.EXT. This should work for both regular filenames
     * and dotfiles.
     */
    public function testDuplicateFile()
    {
        $filenames = ['__phpunit_test_file_delete_me.extension', '.__phpunit_test_dotfile_delete_me'];

        foreach($filenames as $filename) {
            // Create the file
            $this->getService('filesystem')->put(self::FILESYSTEM . '://' . $filename, '');

            $extensionPos = strrpos($filename, '.') ?: strlen($filename);
            $fileBase = substr($filename, 0, $extensionPos) . '_copy';
            $fileExtension = substr($filename, $extensionPos);

            for ($i = 1; $i <= 5; $i++) {
                $destination = $fileBase . $i . $fileExtension;

                // The file shouldn't exist yet
                $this->assertFalse($this->getService('filesystem')->has(self::FILESYSTEM . '://' . $destination));

                $this->setRequest(Request::create('/async/file/duplicate', 'POST', [
                    'namespace' => self::FILESYSTEM,
                    'filename'  => $filename
                ]));

                $response = $this->controller()->duplicateFile($this->getRequest());
                $this->assertInstanceOf(JsonResponse::class, $response);
                $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

                // The copy should now have been created
                $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . $destination));
            }
        }
    }

    public function testRenameFile()
    {
        // Create the file
        $this->setRequest(Request::create('/async/file/create', 'POST', [
            'namespace' => 'files',
            'parent'    => '',
            'filename'   => self::FILE_NAME
        ]));
        $this->controller()->createFile($this->getRequest());
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FILE_NAME));

        // Rename the file
        $this->setRequest(Request::create('/async/file/rename', 'POST', [
            'namespace' => 'files',
            'parent'    => '',
            'oldname'   => self::FILE_NAME,
            'newname'   => self::FILE_NAME_2
        ]));

        $response = $this->controller()->renameFile($this->getRequest());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertFalse($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FILE_NAME));
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FILE_NAME_2));
    }

    public function testDeleteFile()
    {
        $this->setRequest(Request::create('/async/file/delete', 'POST', [
            'namespace' => 'files',
            'filename'  => self::FILE_NAME
        ]));

        // The file should still exist before deleting
        $this->controller()->createFile($this->getRequest());
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FILE_NAME));

        $response = $this->controller()->deleteFile($this->getRequest());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // The file shouldn't exist anymore
        $this->assertFalse($this->getService('filesystem')->has(self::FILESYSTEM . '://' . self::FILE_NAME));

        // Attempting to delete the same file twice (or simply attempting to remove a file that doesn't exist) should
        // return a 404 Not Found status code
        $response = $this->controller()->deleteFile($this->getRequest());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testFilesAutoComplete()
    {
        // First create a bunch of files named FOLDER/$i.EXTENSION
        $prefix = 'autocomplete';
        $extensions = ['ext1', 'ext2'];
        $count = 5;

        for ($i = 1; $i <= $count; $i++) {
            foreach ($extensions as $extension) {
                $this->getService('filesystem')->put(self::FILESYSTEM . '://' . $prefix . $i . '.' . $extension, '');
            }
        }

        // Querying should return all files
        $this->setRequest(Request::create('/async/file/autocomplete', 'GET', [
            'term' => $prefix,
            'ext'  => '.*'
        ]));

        $response = $this->controller()->filesAutoComplete($this->getRequest());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount($count*count($extensions), json_decode($response->getContent()));

        // Filtering by one extension should return only $count files
        $this->setRequest(Request::create('/async/file/autocomplete', 'GET', [
            'term' => $prefix,
            'ext'  => $extensions[0]
        ]));

        $response = $this->controller()->filesAutoComplete($this->getRequest());
        $this->assertCount($count, json_decode($response->getContent()));
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
