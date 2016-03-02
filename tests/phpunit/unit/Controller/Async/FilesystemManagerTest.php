<?php
namespace Bolt\Tests\Controller\Async;

use Bolt\Filesystem\Handler\HandlerInterface;
use Bolt\Response\BoltResponse;
use Bolt\Session\Handler\FileHandler;
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
    const FOLDER_NAME_2 = '__phpunit_test_folder_2_delete_me';

    private $oldFiles = [];

    /**
     * Store the list of files in the files folder so we can delete any added files after we're done testing
     * @before
     */
    public function storeFileList()
    {
        $this->oldFiles = $this->getService('filesystem')->listContents(self::FILESYSTEM . '://');
    }

    /**
     * Remove any files added during the test
     * @after
     */
    public function restoreFileList()
    {
        $newFiles = array_udiff(
            $this->getService('filesystem')->listContents(self::FILESYSTEM . '://'),
            $this->oldFiles,
            function (HandlerInterface $file1, HandlerInterface $file2) {
                return strcmp($file2->getPath(), $file2->getPath());
            }
        );
        /** @var HandlerInterface $file */
        foreach ($newFiles as $file) {
            $file->delete();
        }
    }

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

        foreach ($filenames as $filename) {
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

    /**
     * Test renaming both files and folders, since the controller actions have the same signature and output
     */
    public function testRename()
    {
        $definitions = [
            'file'   => ['old' => self::FILE_NAME, 'new' => self::FILE_NAME_2],
            'folder' => ['old' => self::FOLDER_NAME, 'new' => self::FOLDER_NAME_2]
        ];
        foreach ($definitions as $object => $data) {
            $this->createObject($object, $data['old']);

            // Rename the object
            $response = $this->renameObject($object, $data['old'], $data['new']);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

            $this->assertFalse($this->getService('filesystem')->has(self::FILESYSTEM . '://' . $data['old']));
            $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . $data['new']));
        }
    }

    /**
     * Test the error handling when attempting to rename non existent files and folders and when attemtping to rename to
     * a filename that already exists.
     */
    public function testInvalidRename()
    {
        $definitions = [
            'file'   => ['old' => self::FILE_NAME, 'new' => self::FILE_NAME_2],
            'folder' => ['old' => self::FOLDER_NAME, 'new' => self::FOLDER_NAME_2]
        ];
        foreach ($definitions as $object => $data) {
            /*
             * Object doesn't exist
             */
            $this->createObject($object, $data['old']);
            $response = $this->renameObject($object, $data['old'] . '_nonexistent', $data['new']);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

            /*
             * Destination already exists
             */
            // Create the objects
            foreach ([$data['old'], $data['new']] as $filename) {
                $this->createObject($object, $filename);
            }

            $response = $this->renameObject($object, $data['old'], $data['new']);
            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        }
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
        $this->assertCount($count * count($extensions), json_decode($response->getContent()));

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

    /**
     * @param string $object The type of the object, either 'file' or 'folder'
     * @param string $name The name of the new object
     */
    private function createObject($object, $name)
    {
        $this->setRequest(Request::create("/async/$object/create", 'POST', [
            'namespace'  => 'files',
            'parent'     => '',
            'filename'   => $name,
            'foldername' => $name
        ]));
        switch ($object) {
            case 'file':
                $this->controller()->createFile($this->getRequest());
                break;
            case 'folder':
                $this->controller()->createFolder($this->getRequest());
                break;
        }
        $this->assertTrue($this->getService('filesystem')->has(self::FILESYSTEM . '://' . $name));
    }

    /**
     * @param string $object The type of the object, either 'file' or 'folder'
     * @param string $old
     * @param string $new
     *
     * @return JsonResponse
     */
    private function renameObject($object, $old, $new)
    {
        $this->setRequest(Request::create("/async/$object/rename", 'POST', [
            'namespace' => 'files',
            'parent'    => '',
            'oldname'   => $old,
            'newname'   => $new
        ]));
        switch ($object) {
            case 'file':
                $response = $this->controller()->renameFile($this->getRequest());
                break;
            case 'folder':
                $response = $this->controller()->renameFolder($this->getRequest());
                break;
        }

        return $response;
    }

    /**
     * @return \Bolt\Controller\Async\FilesystemManager
     */
    protected function controller()
    {
        return $this->getService('controller.async.filesystem_manager');
    }
}
