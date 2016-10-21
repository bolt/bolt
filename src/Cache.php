<?php

namespace Bolt;

use Bolt\Filesystem\AggregateFilesystemInterface;
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

    /** @var AggregateFilesystemInterface */
    private $filesystem;

    /**
     * Cache constructor.
     *
     * @param string                       $directory
     * @param string                       $extension
     * @param int                          $umask
     * @param AggregateFilesystemInterface $filesystem
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002, AggregateFilesystemInterface $filesystem = null)
    {
        parent::__construct($directory, $extension, $umask);
        $this->filesystem = $filesystem;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use flushAll() instead.
     */
    public function clearCache()
    {
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

        if ($this->filesystem instanceof AggregateFilesystemInterface) {
            // Clear our own cache folder.
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/development'));
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/production'));
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/profiler'));

            // Clear the thumbs folder.
            $this->flushDirectory($this->filesystem->getFilesystem('web')->getDir('/thumbs'));
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
}
