<?php

namespace Bolt\Tests\Cache;

use Bolt\Cache;
use Bolt\Tests\BoltUnitTest;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;

class CacheTest extends BoltUnitTest
{
    /**
     * @var \Bolt\Cache
     */
    protected $cache;
    /**
     * Real path to cache workspace directory
     *
     * @var string
     */
    protected $workspace;

    public function setUp()
    {
        $app = $this->getApp();
        $path = new PlatformFileSystemPathFactory();
        $this->workspace = $path->createTemporaryPath();
        mkdir($this->workspace, 0777, true);
        $this->workspace = realpath($this->workspace);
        $this->cache = new Cache(
            $this->workspace,
            Cache::EXTENSION,
            0002,
            $app['filesystem']
        );
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
        return [
            [
                'bar',
                'bar',
            ],
            [
                ['foo' => 'bar', 'baz' => 'meh'],
                ['foo' => 'bar', 'baz' => 'meh'],
            ],
            [
                new FooObject(),
                new FooObject(),
            ],
        ];
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

    // Windows can achieve both of these tests therefore it is meaningless there

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNonExistingDirCantBeCreated()
    {
        if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
            throw new \InvalidArgumentException('Win can');
        } else {
            $app = $this->getApp();
            new Cache(
                '/foo/bar/baz',
                Cache::EXTENSION,
                0002,
                $app['filesystem']
            );
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnwriteableCacheDir()
    {
        if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
            throw new \InvalidArgumentException('Win can');
        } else {
            $this->clean($this->workspace);
            mkdir($this->workspace, 0400);
            $app = $this->getApp();
            $this->cache = new Cache(
                $this->workspace,
                Cache::EXTENSION,
                0002,
                $app['filesystem']
            );
        }
    }
}
