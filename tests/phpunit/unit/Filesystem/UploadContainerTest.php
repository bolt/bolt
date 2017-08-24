<?php

namespace Bolt\Tests\Filesystem;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\UploadContainer;
use Bolt\Tests\BoltUnitTest;
use League\Flysystem\Memory\MemoryAdapter;

/**
 * @covers \Bolt\Filesystem\UploadContainer
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class UploadContainerTest extends BoltUnitTest
{
    /** @var UploadContainer */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $adapter = new MemoryAdapter();
        $fs = new Filesystem($adapter);
        $fs->put('koala.txt', 'koala');

        $this->container = new UploadContainer($fs);
    }

    public function testIsWritable()
    {
        $this->assertTrue($this->container->isWritable());
    }

    public function testHas()
    {
        $result = $this->container->has('nonexistent');
        $this->assertFalse($result);

        $result = $this->container->has('koala.txt');
        $this->assertTrue($result);
    }

    public function testSave()
    {
        $this->container->save('filename', 'content');

        $result = $this->container->has('filename');
        $this->assertTrue($result);
    }

    /**
     * @expectedException \Bolt\Filesystem\Exception\FileNotFoundException
     */
    public function testDelete()
    {
        $this->container->delete('filename');
    }

    public function testMoveUploadedFile()
    {
        $this->container->moveUploadedFile(__FILE__, 'dropbear.txt');

        $result = $this->container->has('dropbear.txt');
        $this->assertTrue($result);
    }
}
