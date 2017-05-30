<?php

namespace Bolt;

use Bolt\Filesystem\AggregateFilesystemInterface;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\HandlerInterface;
use Bolt\Helpers\Deprecated;
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

    /** @var bool */
    private $clearThumbs;

    /**
     * Cache constructor.
     *
     * @param string                       $directory
     * @param string                       $extension
     * @param int                          $umask
     * @param AggregateFilesystemInterface $filesystem
     * @param bool                         $clearThumbs
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002, AggregateFilesystemInterface $filesystem = null, $clearThumbs = true)
    {
        parent::__construct($directory, $extension, $umask);
        $this->filesystem = $filesystem;
        $this->clearThumbs = $clearThumbs;
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

        if ($this->filesystem instanceof AggregateFilesystemInterface) {
            try {
                // Clear our cached configuration
                $this->filesystem->getFilesystem('cache')->delete('config-cache.json');
            } catch (Filesystem\Exception\FileNotFoundException $e) {
                // Ç'est la vie
            }

            // Clear our own cache folder.
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/development'));
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/exception'));
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/production'));
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/profiler'));
            $this->flushDirectory($this->filesystem->getFilesystem('cache')->getDir('/trans'));

            if ($this->clearThumbs) {
                // Clear the thumbs folder.
                $this->flushDirectory($this->filesystem->getFilesystem('web')->getDir('/thumbs'));
            }
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
