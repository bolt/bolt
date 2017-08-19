<?php

namespace Bolt;

use Bolt\Common\Deprecated;
use Bolt\Filesystem\CompositeFilesystemInterface;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\HandlerInterface;
use Doctrine\Common\Cache\FilesystemCache;

/**
 * Simple, file based cache for volatile data. Useful for storing non-vital
 * information like feeds, and other stuff that can be recovered easily.
 *
 * @author Bob den Otter <bob@twokings.nl>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class Cache extends FilesystemCache
{
    /** Max cache age. Default 10 minutes. */
    const DEFAULT_MAX_AGE = 600;
    /** Default cache file extension. */
    const EXTENSION = '.data';

    /** @var CompositeFilesystemInterface */
    private $filesystem;
    /** @var int */
    private $umask;

    /**
     * Cache constructor.
     *
     * @param string                       $directory
     * @param string                       $extension
     * @param int                          $umask
     * @param CompositeFilesystemInterface $filesystem
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002, CompositeFilesystemInterface $filesystem = null)
    {
        umask($umask);
        $this->filesystem = $filesystem;
        $this->umask = $umask;
        parent::__construct($directory, $extension, $umask);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use flushAll() instead.
     */
    public function clearCache()
    {
        Deprecated::method(3.0, 'flushAll');

        $this->flushAll();

        return [
            'successfiles'   => 0,
            'failedfiles'    => 0,
            'failed'         => [],
            'successfolders' => 0,
            'failedfolders'  => 0,
            'log'            => '',
        ];
    }

    /**
     * Clear the cache. Both the doctrine FilesystemCache, as well as twig and thumbnail temp files.
     *
     * @return bool
     */
    protected function doFlush()
    {
        // Clear Doctrine's folder.
        $result = parent::doFlush();

        if ($this->filesystem instanceof CompositeFilesystemInterface) {
            $cacheFs = $this->filesystem->getFilesystem('cache');
            // Clear our cached configuration
            if ($cacheFs->has('config-cache.json')) {
                $cacheFs->delete('config-cache.json');
            }

            // Clear our own cache folder.
            $this->flushDirectory($cacheFs->getDir('/development'));
            $this->flushDirectory($cacheFs->getDir('/exception'));
            $this->flushDirectory($cacheFs->getDir('/production'));
            $this->flushDirectory($cacheFs->getDir('/profiler'));
            $this->flushDirectory($cacheFs->getDir('/trans'));

            // Clear the thumbs folder.
            $this->flushDirectory($this->filesystem->getFilesystem('web')->getDir('/thumbs'));

            // We need to recreate our base Doctrine cache directory, as it
            // will be a subdirectory of one of the ones we just wiped.
            $this->createPathIfNeeded();
        }

        return $result;
    }

    /**
     * Helper function for doFlush().
     *
     * @param DirectoryInterface $directory
     */
    private function flushDirectory(DirectoryInterface $directory)
    {
        if (!$directory->exists()) {
            return;
        }

        $files = $directory->find()
            ->ignoreDotFiles()
            ->ignoreVCS()
        ;

        /** @var HandlerInterface $file */
        foreach ($files as $file) {
            try {
                $file->delete();
            } catch (IOException $e) {
            }
        }
    }

    /**
     * Create base path path if needed.
     *
     * @return bool
     */
    private function createPathIfNeeded()
    {
        if (!is_dir($this->directory)) {
            return @mkdir($this->directory, 0777 & (~$this->umask), true) && !is_dir($this->directory);
        }

        return true;
    }
}
