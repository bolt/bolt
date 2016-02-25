<?php

namespace Bolt\Debug\Caster;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Handler\HandlerInterface;
use Bolt\Filesystem\Handler\Image;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * Casters for Filesystem objects.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemCasters extends AbstractCasterProvider
{
    protected static function defineCasters()
    {
        return [
            HandlerInterface::class   => 'castHandler',
            FileInterface::class      => 'castFile',
            DirectoryInterface::class => 'castDirectory',
            Image::class              => 'castImage',
            Image\Info::class         => 'castImageInfo',
            Image\Type::class         => 'castImageType',
            Image\Dimensions::class   => 'castDimensions',
            Image\Exif::class         => 'castImageExif',
        ];
    }

    public static function castHandler(HandlerInterface $handler, array $a, Stub $stub, $isNested, $filter = 0)
    {
        // Populate lazy loaded properties
        $a[Caster::PREFIX_PROTECTED . 'fullPath'] = $handler->getFullPath();
        $a[Caster::PREFIX_PROTECTED . 'dirname'] = $handler->getDirname();
        $a[Caster::PREFIX_PROTECTED . 'filename'] = $handler->getFilename();
        $a[Caster::PREFIX_PROTECTED . 'exists'] = $exists = $handler->exists();

        if ($exists) {
            $a[Caster::PREFIX_PROTECTED . 'type'] = $handler->getType();
            $a[Caster::PREFIX_PROTECTED . 'timestamp'] = $handler->getTimestamp();
            $a[Caster::PREFIX_PROTECTED . 'visibility'] = $handler->getVisibility();
        } else {
            unset($a[Caster::PREFIX_PROTECTED . 'type']);
            unset($a[Caster::PREFIX_PROTECTED . 'timestamp']);
            unset($a[Caster::PREFIX_PROTECTED . 'visibility']);
        }

        // Some methods return results instead of setting properties, show these as virtual.
        if ($ext = $handler->getExtension()) {
            $a[Caster::PREFIX_VIRTUAL . 'extension'] = $ext;
        }

        if (!$exists) {
            return $a;
        }

        $a[Caster::PREFIX_VIRTUAL . 'dir'] = $handler->isDir();
        $a[Caster::PREFIX_VIRTUAL . 'file'] = $handler->isFile();
        $a[Caster::PREFIX_VIRTUAL . 'image'] = $handler->isImage();
        $a[Caster::PREFIX_VIRTUAL . 'document'] = $handler->isDocument();
        $a[Caster::PREFIX_VIRTUAL . 'carbon'] = $handler->getCarbon();
        $a[Caster::PREFIX_VIRTUAL . 'public'] = $handler->isPublic();
        $a[Caster::PREFIX_VIRTUAL . 'private'] = $handler->isPrivate();

        return $a;
    }

    public static function castFile(FileInterface $file, array $a, Stub $stub, $isNested, $filter = 0)
    {
        if ($file->exists()) {
            $a[Caster::PREFIX_PROTECTED . 'mimetype'] = $file->getMimeType();
            $a[Caster::PREFIX_PROTECTED . 'size'] = $file->getSize();
            $a[Caster::PREFIX_VIRTUAL . 'sizeFormatted'] = $file->getSizeFormatted();
        } else {
            unset($a[Caster::PREFIX_PROTECTED . 'mimetype']);
            unset($a[Caster::PREFIX_PROTECTED . 'size']);
        }

        return $a;
    }

    public static function castDirectory(DirectoryInterface $directory, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $a[Caster::PREFIX_VIRTUAL . 'root'] = $directory->isRoot();

        return $a;
    }

    public static function castImage(Image $image, array $a, Stub $stub, $isNested, $filter = 0)
    {
        try {
            $a[Caster::PREFIX_PROTECTED . 'info'] = $image->getInfo();
        } catch (IOException $e) {
        }

        return $a;
    }

    public static function castImageInfo(Image\Info $info, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $a[Caster::PREFIX_VIRTUAL . 'width'] = $info->getWidth();
        $a[Caster::PREFIX_VIRTUAL . 'height'] = $info->getHeight();

        return $a;
    }

    public static function castImageType(Image\Type $type, array $a, Stub $stub, $isNested, $filter = 0)
    {
        unset($a["\0Bolt\\Filesystem\\Handler\\Image\\Type\0name"]);

        $a[Caster::PREFIX_VIRTUAL . 'string'] = $type->toString();
        $a[Caster::PREFIX_VIRTUAL . 'mimeType'] = $type->getMimeType();
        $a[Caster::PREFIX_VIRTUAL . 'extension'] = $type->getExtension();
        $stub->class .= sprintf(' "%s"', $type->toString());

        return $a;
    }

    public static function castDimensions(Image\Dimensions $dimensions, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $stub->class .= sprintf(' "%s"', (string) $dimensions);

        return $a;
    }

    public static function castImageExif(Image\Exif $exif, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $a = $a[Caster::PREFIX_PROTECTED . 'data'];

        $a[Caster::PREFIX_VIRTUAL . 'aspectRatio'] = $exif->getAspectRatio();
        $a[Caster::PREFIX_VIRTUAL . 'latitude'] = $exif->getLatitude();
        $a[Caster::PREFIX_VIRTUAL . 'longitude'] = $exif->getLongitude();

        return $a;
    }
}
