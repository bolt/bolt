<?php

namespace Bolt\Filesystem;

use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Handler\ImageInterface;

/**
 * This is designed to help migrate us to our new filesystem abstraction which
 * requires mount points. During this transition period we need to be able to
 * match paths without mount points to a filesystem.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Matcher
{
    /** @var FilesystemInterface */
    protected $filesystem;
    /** @var string[] */
    protected $filesystemsToCheck;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param string[]            $filesystemsToCheck
     */
    public function __construct(FilesystemInterface $filesystem, array $filesystemsToCheck)
    {
        $this->filesystem = $filesystem;
        $this->filesystemsToCheck = $filesystemsToCheck;
    }

    /**
     * Gets the file object for the path given. Paths with the mount point included are
     * preferred, but are not required for BC. If the mount point is not included a list
     * of filesystems are checked and chosen if the file exists in that filesystem.
     *
     * @param FileInterface|string $path
     * @param bool                 $throwException
     *
     * @throws FileNotFoundException if file was not found
     *
     * @return FileInterface
     */
    public function getFile($path, $throwException = true)
    {
        if ($path instanceof FileInterface) {
            return $path;
        }

        if (!$this->filesystem instanceof AggregateFilesystemInterface || $this->containsMountPoint($path)) {
            $file = $this->filesystem->getFile($path);
            if ($file->exists()) {
                return $file;
            }
            if ($throwException) {
                throw new FileNotFoundException($path);
            }

            return null;
        }

        // Trim "files/" from front of path for BC.
        if (strpos($path, 'files/') === 0) {
            $path = substr($path, 6);
        }

        foreach ($this->filesystemsToCheck as $mountPoint) {
            if (!$this->filesystem->hasFilesystem($mountPoint)) {
                continue;
            }

            $file = $this->filesystem->getFile("$mountPoint://$path");
            if ($file->exists()) {
                return $file;
            }
        }

        if ($throwException) {
            throw new FileNotFoundException($path);
        }

        return null;
    }

    /**
     * Same as {@see getFile} for images.
     *
     * @param ImageInterface|string $path
     * @param bool                  $throwException
     *
     * @throws FileNotFoundException if file was not found
     *
     * @return ImageInterface
     */
    public function getImage($path, $throwException = true)
    {
        if ($path instanceof ImageInterface) {
            return $path;
        }

        $file = $this->getFile($path, $throwException);
        if ($file) {
            return $this->filesystem->getImage($file->getFullPath());
        }

        return null;
    }

    /**
     * Change if a path contains a mount point.
     *
     * Ex: files://foo.jpg
     *
     * @param string $path
     *
     * @return bool
     */
    private function containsMountPoint($path)
    {
        return (bool) preg_match('#^.+\:\/\/.*#', $path);
    }
}
