<?php

namespace Bolt\Tests;

use Bolt\Cache;
use Symfony\Component\Filesystem\Filesystem;
use Eloquent\Pathogen\FileSystem\PlatformFileSystemPath;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

class CacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Bolt\Cache
     */
    protected $cache;
    /**
     * Real path to cache workspace directory
     * @var string
     */
    protected $workspace;

    public function setUp()
    {
        $path = new PlatformFileSystemPathFactory();
        $this->workspace = $path->createTemporaryPath();
        mkdir($this->workspace, 0777, true);
        $this->workspace = realpath($this->workspace);
        $this->cache = new Cache($this->workspace);
    }

    public function tearDown()
    {
        $this->cache->flushAll();
        $this->clean($this->workspace);
    }

    /**
     * @param string $file
     */
    private function clean($file)
    {
        if (is_dir($file) && !is_link($file)) {
            $dir = new \FilesystemIterator($file);
            foreach ($dir as $childFile) {
                $this->clean($childFile);
            }

            rmdir($file);
        } else {
            unlink($file);
        }
    }

    /**
     * @return array
     */
    public static function setProvider()
    {
        return array(
            array(
                'bar',
                'bar'
            ),
            array(
                array('foo' => 'bar', 'baz' => 'meh'),
                array('foo' => 'bar', 'baz' => 'meh')
            ),
            array(
                new \Bolt\Tests\FooObject(),
                new \Bolt\Tests\FooObject()
            )
        );
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($value, $expected)
    {
        $key = 'foo';
        $this->cache->save($key, $value);
        $this->assertEquals($expected, $this->cache->fetch($key));
    }

    /**
     * Checks if giving a relative path results in the same path under water.
     */
    public function testCacheDirLocation()
    {
        $cacheDirLocation = $this->cache->getDirectory();
        $filesystem = new Filesystem();
        $relative = $filesystem->makePathRelative($cacheDirLocation, realpath(__DIR__ . '/../../../src'));
        $newCache = new Cache($relative);
        $this->assertEquals($cacheDirLocation, $newCache->getDirectory());
    }

    // Windows can achieve both of these tests therefore it is meaningless there

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNonExistingDirCantBeCreated()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)=='WIN')) {
            throw new \InvalidArgumentException('Win can');
        }
        else {
            $newCache = new Cache("/foo/bar/baz");
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnwriteableCacheDir()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)=='WIN')) {
            throw new \InvalidArgumentException('Win can');
        } else {
            $this->clean($this->workspace);
            mkdir($this->workspace, 0400);
            $this->cache = new Cache($this->workspace);
        }
    }

}
