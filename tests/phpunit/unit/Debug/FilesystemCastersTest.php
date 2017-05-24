<?php

namespace Bolt\Tests\Debug;

use Bolt\Debug\Caster\FilesystemCasters;
use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Image;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * @covers \Bolt\Debug\Caster\FilesystemCasters
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FilesystemCastersTest extends TestCase
{
    public function providerDefinedCasters()
    {
        return [
            ['Bolt\Filesystem\Handler\HandlerInterface', 'castHandler'],
            ['Bolt\Filesystem\Handler\FileInterface', 'castFile'],
            ['Bolt\Filesystem\Handler\DirectoryInterface', 'castDirectory'],
            ['Bolt\Filesystem\Handler\Image', 'castImage'],
            ['Bolt\Filesystem\Handler\Image\Info', 'castImageInfo'],
            ['Bolt\Filesystem\Handler\Image\Type', 'castImageType'],
            ['Bolt\Filesystem\Handler\Image\Dimensions', 'castDimensions'],
            ['Bolt\Filesystem\Handler\Image\Exif', 'castImageExif'],
        ];
    }

    /**
     * @dataProvider providerDefinedCasters
     *
     * @param string $name
     * @param string $method
     */
    public function testDefinedCasters($name, $method)
    {
        $casters = FilesystemCasters::getCasters();
        $this->assertArrayHasKey($name, $casters);
        $this->assertSame($method, $casters[$name][1]);
    }

    public function testDefinedCastersCount()
    {
        $casters = FilesystemCasters::getCasters();
        $this->assertCount(8, array_keys($casters));
    }

    public function providerCastHandler()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $file = $fs->getFile('generic-logo.png');

        $result = FilesystemCasters::castHandler($file, [], $stub, false);

        return [
            [Caster::PREFIX_PROTECTED . 'fullPath', $file->getFullPath(), $result],
            [Caster::PREFIX_PROTECTED . 'dirname', $file->getDirname(), $result],
            [Caster::PREFIX_PROTECTED . 'filename', $file->getFilename(), $result],
            [Caster::PREFIX_PROTECTED . 'exists', $file->exists(), $result],
            [Caster::PREFIX_PROTECTED . 'type', $file->getType(), $result],
            [Caster::PREFIX_PROTECTED . 'timestamp', $file->getTimestamp(), $result],
            [Caster::PREFIX_PROTECTED . 'visibility', $file->getVisibility(), $result],
            [Caster::PREFIX_VIRTUAL . 'extension', $file->getExtension(), $result],
            [Caster::PREFIX_VIRTUAL . 'dir', false, $result],
            [Caster::PREFIX_VIRTUAL . 'file', true, $result],
            [Caster::PREFIX_VIRTUAL . 'image', true, $result],
            [Caster::PREFIX_VIRTUAL . 'document', false, $result],
            [Caster::PREFIX_VIRTUAL . 'carbon', $file->getCarbon()->getTimestamp(), $result],
            [Caster::PREFIX_VIRTUAL . 'public', $file->isPublic(), $result],
            [Caster::PREFIX_VIRTUAL . 'private', $file->isPrivate(), $result],
        ];
    }

    /**
     * @dataProvider providerCastHandler
     *
     * @param string $key
     * @param mixed  $expectation
     * @param array  $result
     */
    public function testCastHandler($key, $expectation, array $result)
    {
        $this->assertArrayHasKey($key, $result);

        if ($key === Caster::PREFIX_VIRTUAL . 'carbon') {
            $this->assertSame($expectation, $result[Caster::PREFIX_VIRTUAL . 'carbon']->getTimestamp());
        } else {
            $this->assertSame($expectation, $result[$key]);
        }
    }

    public function testCastHandlerMissing()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $file = $fs->getFile('missing-koala.jpg');
        $a = [
            Caster::PREFIX_PROTECTED . 'type'       => null,
            Caster::PREFIX_PROTECTED . 'timestamp'  => null,
            Caster::PREFIX_PROTECTED . 'visibility' => null,
        ];

        $result = FilesystemCasters::castHandler($file, $a, $stub, false);

        $this->assertArrayNotHasKey(Caster::PREFIX_PROTECTED . 'type', $result);
        $this->assertArrayNotHasKey(Caster::PREFIX_PROTECTED . 'timestamp', $result);
        $this->assertArrayNotHasKey(Caster::PREFIX_PROTECTED . 'visibility', $result);

        $this->assertSame($file->getFullPath(), $result[Caster::PREFIX_PROTECTED . 'fullPath']);
    }

    public function testCastFile()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $file = $fs->getFile('generic-logo.png');

        $result = FilesystemCasters::castFile($file, [], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_PROTECTED . 'mimetype', $result);
        $this->assertArrayHasKey(Caster::PREFIX_PROTECTED . 'size', $result);
        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'sizeFormatted', $result);

        $this->assertSame($file->getMimeType(), $result[Caster::PREFIX_PROTECTED . 'mimetype']);
        $this->assertSame($file->getSize(), $result[Caster::PREFIX_PROTECTED . 'size']);
        $this->assertSame($file->getSizeFormatted(), $result[Caster::PREFIX_VIRTUAL . 'sizeFormatted']);
    }

    public function testCastFileMissing()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $file = $fs->getFile('missing-koala.jpg');
        $a = [
            Caster::PREFIX_PROTECTED . 'mimetype' => null,
            Caster::PREFIX_PROTECTED . 'size'     => null,
        ];

        $result = FilesystemCasters::castFile($file, $a, $stub, false);

        $this->assertArrayNotHasKey(Caster::PREFIX_PROTECTED . 'mimetype', $result);
        $this->assertArrayNotHasKey(Caster::PREFIX_PROTECTED . 'size', $result);
    }

    public function testCastDirectoryRoot()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $dir = $fs->getDir('');

        $result = FilesystemCasters::castDirectory($dir, [], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'root', $result);
        $this->assertTrue($result[Caster::PREFIX_VIRTUAL . 'root']);
    }

    public function testCastDirectorySub()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $dir = $fs->getDir('db');

        $result = FilesystemCasters::castDirectory($dir, [], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'root', $result);
        $this->assertFalse($result[Caster::PREFIX_VIRTUAL . 'root']);
    }

    public function testCastImage()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        /** @var Image $image */
        $image = $fs->getFile('generic-logo.png');

        $result = FilesystemCasters::castImage($image, [], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_PROTECTED . 'info', $result);
        $this->assertSame($image->getInfo(), $result[Caster::PREFIX_PROTECTED . 'info']);
    }

    public function testCastImageInvalid()
    {
        $stub = new Stub();
        $fs = new Filesystem(new Local(__DIR__ . '/../resources'));
        $image = $fs->getFile('nothing.png');

        $result = FilesystemCasters::castImage($image, [Caster::PREFIX_PROTECTED . 'info' => null], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_PROTECTED . 'info', $result);
        $this->assertNull($result[Caster::PREFIX_PROTECTED . 'info'], $result);
    }

    public function testCastImageInfo()
    {
        $stub = new Stub();
        $dimensions = new Image\Dimensions(1200, 600);
        $types = Image\CoreType::getTypes();
        $type = $types[2];
        $info = new Image\Info($dimensions, $type, 24, 1, $type->getMimeType(), new Image\Exif());

        $result = FilesystemCasters::castImageInfo($info, [], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'width', $result);
        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'height', $result);

        $this->assertSame($info->getWidth(), $result[Caster::PREFIX_VIRTUAL . 'width']);
        $this->assertSame($info->getHeight(), $result[Caster::PREFIX_VIRTUAL . 'height']);
    }

    public function testCastImageType()
    {
        $stub = new Stub();
        $types = Image\CoreType::getTypes();
        $type = $types[2];

        $result = FilesystemCasters::castImageType($type, [], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'string', $result);
        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'mimeType', $result);
        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'extension', $result);

        $this->assertSame($type->toString(), $result[Caster::PREFIX_VIRTUAL . 'string']);
        $this->assertSame($type->getMimeType(), $result[Caster::PREFIX_VIRTUAL . 'mimeType']);
        $this->assertSame($type->getExtension(), $result[Caster::PREFIX_VIRTUAL . 'extension']);

        $this->assertSame(sprintf(' "%s"', $type->toString()), $stub->class);
    }

    public function testCastDimensions()
    {
        $stub = new Stub();
        $dimensions = new Image\Dimensions(1200, 600);

        FilesystemCasters::castDimensions($dimensions, [], $stub, false);

        $this->assertSame(' "1200 × 600 px"', $stub->class);
    }

    public function testCastImageExif()
    {
        $stub = new Stub();
        $exif = new Image\Exif();
        $exif->setHeight(600);
        $exif->setWidth(1200);
        $exif->setGPS('42,24');

        $result = FilesystemCasters::castImageExif($exif, [Caster::PREFIX_PROTECTED . 'data' => []], $stub, false);

        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'aspectRatio', $result);
        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'latitude', $result);
        $this->assertArrayHasKey(Caster::PREFIX_VIRTUAL . 'longitude', $result);

        $this->assertSame(2, $result[Caster::PREFIX_VIRTUAL . 'aspectRatio']);
        $this->assertSame(42.0, $result[Caster::PREFIX_VIRTUAL . 'latitude']);
        $this->assertSame(24.0, $result[Caster::PREFIX_VIRTUAL . 'longitude']);
    }
}
