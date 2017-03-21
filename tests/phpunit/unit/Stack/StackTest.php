<?php

namespace Bolt\Tests\Stack;

use Bolt\Filesystem;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Stack;
use Bolt\Tests\BoltUnitTest;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class to test src/Stack.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class StackTest extends BoltUnitTest
{
    /** @var Stack */
    private $stack;
    /** @var Filesystem\FilesystemInterface */
    private $filesystem;
    /** @var \Bolt\Users|MockObject */
    private $users;
    /** @var SessionInterface */
    private $session;
    /** @var string[] */
    private $acceptedFileTypes = ['twig', 'html', 'js', 'css', 'scss', 'gif', 'jpg', 'jpeg', 'png', 'ico', 'zip', 'tgz', 'txt', 'md', 'doc', 'docx', 'pdf', 'epub', 'xls', 'xlsx', 'ppt', 'pptx', 'mp3', 'ogg', 'wav', 'm4a', 'mp4', 'm4v', 'ogv', 'wmv', 'avi', 'webm', 'svg'];

    protected function setUp()
    {
        $app = $this->getApp();
        $this->users = $this->getMockUsers(['getCurrentUser', 'saveUser']);
        $this->session = new Session(
            new MockArraySessionStorage()
        );
        $this->filesystem = new Filesystem\Manager([
            'files'  => new Filesystem\Filesystem(new MemoryAdapter()),
            'theme'  => new Filesystem\Filesystem(new MemoryAdapter()),
        ]);
        $matcher = new Filesystem\Matcher(
            $this->filesystem,
            ['files', 'themes', 'theme']
        );

        $this->stack = new Stack(
            $matcher,
            $this->users,
            $this->session,
            $this->acceptedFileTypes
        );

        $this->session->set('stack', [
            'a.jpg',
            'b.txt',
            'files://c.txt',
            'd.doc',
            'e.mp3',
            'theme://f.txt',
            'g.txt',
            'does_not_exist.txt',
            'h.txt',
        ]);

        $this->createFiles([
            'files://a.jpg',
            'files://b.txt',
            'files://c.txt',
            'files://d.doc',
            'files://e.mp3',
            'theme://f.txt',
            'theme://g.txt',
            'files://h.txt',
            'files://evil.exe',
        ]);
    }

    public function testContainsAndInitializeFromSession()
    {
        $this->users->expects($this->never())
            ->method('getCurrentUser');

        $this->assertTrue($this->stack->contains('a.jpg'), 'Stack::contains should match file paths without mount points');
        $this->assertTrue($this->stack->contains('g.txt'), 'Stack::contains should match file paths without mount points');
        $this->assertTrue($this->stack->contains('files://a.jpg'), 'Stack::contains should match file paths with mount points');
        $this->assertTrue($this->stack->contains('files://c.txt'), 'Stack should initialize file paths with mount points');
        $file = $this->filesystem->getFile('files://a.jpg');
        $this->assertTrue($this->stack->contains($file), 'Stack::contains should match file objects');
        $this->assertTrue($this->stack->contains('files/a.jpg'), 'Stack should strip "files/" from start of path');

        $this->assertFalse($this->stack->contains('does_not_exist.txt'), 'Stack should not contain nonexistent files');

        $this->assertFalse($this->stack->contains('h.txt'), 'Stack should trim list to max items on initialize');
    }

    public function testInitializeFromDatabase()
    {
        $this->session->remove('stack');

        $this->users->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn([
                'stack' => [
                    'a.txt',
                ],
            ])
        ;
        $this->filesystem->put('files://a.txt', '');

        $this->stack->getList();

        $this->assertEquals(
            ['files://a.txt'],
            $this->session->get('stack'),
            'Initializing stack from database should cache list in session'
        );
    }

    public function testList()
    {
        $files = $this->stack->getList();

        $this->assertCount(7, $files);
        $this->assertTrue($files[0] instanceof FileInterface, 'Should be a list of file objects');

        $this->assertFiles($files, [
            'files://a.jpg',
            'files://b.txt',
            'files://c.txt',
            'files://d.doc',
            'files://e.mp3',
            'theme://f.txt',
            'theme://g.txt',
        ]);

        $this->assertFiles($this->stack->getList(['image']), [
            'files://a.jpg',
        ], 'List should only contain image files');

        $this->assertFiles($this->stack->getList(['document']), [
            'files://b.txt',
            'files://c.txt',
            'files://d.doc',
            'theme://f.txt',
            'theme://g.txt',
        ], 'List should only contain document files');

        $this->assertFiles($this->stack->getList(['other']), [
            'files://e.mp3',
        ], 'List should only contain non image and document files');

        $this->assertFiles($this->stack->getList(['image', 'document']), [
            'files://a.jpg',
            'files://b.txt',
            'files://c.txt',
            'files://d.doc',
            'theme://f.txt',
            'theme://g.txt',
        ], 'List should contain only image and document files');
    }

    public function testIterable()
    {
        $this->assertTrue($this->stack instanceof \Traversable, 'Stack should be traversable');

        $files = iterator_to_array($this->stack, false);
        $this->assertFiles($files, [
            'files://a.jpg',
            'files://b.txt',
            'files://c.txt',
            'files://d.doc',
            'files://e.mp3',
            'theme://f.txt',
            'theme://g.txt',
        ], 'Iterating over Stack should yield all of the files on the stack');
    }

    public function testCountable()
    {
        $this->assertCount(7, $this->stack, 'Stack should be countable and match the count of files on stack');
    }

    public function testIsStackable()
    {
        $this->assertFalse($this->stack->isStackable('files://a.jpg'), 'Files on stack should not be stackable');
        $this->assertFalse($this->stack->isStackable('files://non_existent_file.txt'), 'Non existent files should not be stackable');
        $this->assertFalse($this->stack->isStackable('files://evil.exe'), 'Unaccepted file extensions should not be stackable');
    }

    public function testDelete()
    {
        $count = count($this->stack);

        $this->stack->delete('non_existent_file');
        $this->assertEquals($count, count($this->stack), 'Deleting a non existent file should do nothing');

        $this->stack->delete('files://h.txt');
        $this->assertEquals($count, count($this->stack), 'Deleting a file not on the stack should do nothing');

        $expectedList = [
            'files://a.jpg',
            'files://c.txt',
            'files://d.doc',
            'files://e.mp3',
            'theme://f.txt',
            'theme://g.txt',
        ];

        $this->users->expects($this->once())
            ->method('saveUser')
            ->with([
                'stack' => $expectedList,
            ]);

        $this->stack->delete('files://b.txt');
        $this->assertFiles($this->stack->getList(), $expectedList, 'Deleting a file on the stack should remove it');
        $this->assertEquals($expectedList, $this->session->get('stack'), 'Deleting a file on the stack should persist removal to session');
    }

    public function testAddNewFile()
    {
        $expectedList = [
            'files://h.txt',
            'files://a.jpg',
            'files://b.txt',
            'files://c.txt',
            'files://d.doc',
            'files://e.mp3',
            'theme://f.txt',
        ];

        $this->users->expects($this->once())
            ->method('saveUser')
            ->with([
                'stack' => $expectedList,
            ]);

        $file = $this->stack->add('h.txt', $removed);
        $this->assertTrue($file instanceof FileInterface, 'File object should be returned from add method');
        $this->assertEquals('files://h.txt', $file->getFullPath(), 'File object should be returned from add method');
        $this->assertTrue($removed instanceof FileInterface, 'Add method should set the removed parameter to the file object removed');
        $this->assertEquals('theme://g.txt', $removed->getFullPath(), 'Removed file should be the last file on the stack before the new one was added');

        $this->assertFiles($this->stack->getList(), $expectedList, 'Adding new file should prepend it to the stack and remove the oldest file');
        $this->assertEquals($expectedList, $this->session->get('stack'), 'Adding a file to the stack should persist change to session');
    }

    public function testAddNewFileWithEmptyStack()
    {
        $this->session->set('stack', []);

        $this->stack->add('a.jpg');
        $this->stack->add('b.txt', $removed);

        $this->assertNull($removed, 'Add methods removed parameter should be optional and null if no file was removed off stack');
    }

    /**
     * @expectedException \Bolt\Exception\FileNotStackableException
     */
    public function testAddExistingFile()
    {
        $this->stack->add('d.doc');
    }

    /**
     * @expectedException \Bolt\Exception\FileNotStackableException
     */
    public function testAddUnacceptableFile()
    {
        $this->stack->add('evil.exe');
    }

    /**
     * @expectedException \Bolt\Filesystem\Exception\FileNotFoundException
     */
    public function testAddNonExistentFile()
    {
        $this->stack->add('non_existent_file');
    }

    protected function createFiles(array $paths)
    {
        foreach ($paths as $path) {
            $this->filesystem->put($path, '');
        }
    }

    protected function assertFiles(array $files, array $expected, $message = '')
    {
        $paths = array_map(function (FileInterface $file) {
            return $file->getFullPath();
        }, $files);
        $this->assertEquals($expected, $paths, $message);
    }
}
