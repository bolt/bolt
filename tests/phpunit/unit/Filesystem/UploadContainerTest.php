<?php

namespace Bolt\Tests\Filesystem;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\UploadContainer;
use Bolt\Tests\BoltUnitTest;
use League\Flysystem\Adapter\NullAdapter;

/**
 * Class to test src/Filesystem/UploadContainerTest.
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

        $app = $this->getApp();

        $adapter = new NullAdapter(TEST_ROOT);
        $fs = new Filesystem($adapter);

        $this->container = new UploadContainer($fs);
    }

    public function testIsWritable()
    {
        $this->assertTrue($this->container->isWritable());
    }

    public function testHas()
    {
        $this->container->has('nonexistent');
    }

    public function testSave()
    {
        $this->container->save('filename', 'content');
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
        $this->container->moveUploadedFile(__FILE__, 'destination');
    }
}
