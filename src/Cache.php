<?php

namespace Bolt;

use Bolt\Configuration\ResourceManager;
use Doctrine\Common\Cache\FilesystemCache;
use Silex;

/**
 * Simple, file based cache for volatile data.. Useful for storing non-vital
 * information like feeds, and other stuff that can be recovered easily.
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Cache extends FilesystemCache
{
    /** Max cache age. Default 10 minutes. */
    const DEFAULT_MAX_AGE = 600;
    /** Default cache file extension. */
    const EXTENSION = '.data';

    /** @var ResourceManager */
    private $resourceManager;
    /** @var string */
    private $boltVersion;

    /**
     * Cache constructor.
     *
     * @param string          $directory
     * @param string          $extension
     * @param int             $umask
     * @param ResourceManager $resourceManager
     * @param string          $boltVersion
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002, ResourceManager $resourceManager = null, $boltVersion = 'Bolt')
    {
        parent::__construct($directory, $extension, $umask);
        $this->resourceManager = $resourceManager;
        $this->boltVersion = $boltVersion;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use doFlush() instead.
     */
    public function clearCache()
    {
        return $this->doFlush();
    }

    /**
     * Clear the cache. Both the doctrine FilesystemCache, as well as twig and thumbnail temp files.
     *
     * @see flushHelper
     */
    public function doFlush()
    {
        $result = [
            'successfiles'   => 0,
            'failedfiles'    => 0,
            'failed'         => [],
            'successfolders' => 0,
            'failedfolders'  => 0,
            'log'            => '',
        ];

        // Clear Doctrine's folder.
        parent::doFlush();

        // Clear our own cache folder.
        $this->flushHelper($this->getDirectory(), '', $result);

        // Clear the thumbs folder.
        $this->flushHelper($this->resourceManager->getPath('web/thumbs'), '', $result);

        return $result;
    }

    /**
     * Helper function for doFlush().
     *
     * @param string $startFolder
     * @param string $additional
     * @param array  $result
     */
    private function flushHelper($startFolder, $additional, &$result)
    {
        $currentfolder = realpath($startFolder . '/' . $additional);

        if (!file_exists($currentfolder)) {
            $result['log'] .= "Folder $currentfolder doesn't exist.<br>";

            return;
        }

        $dir = dir($currentfolder);

        while (($entry = $dir->read()) !== false) {
            $exclude = ['.', '..', '.assetsalt', '.gitignore', 'index.html', '.version'];

            if (in_array($entry, $exclude)) {
                continue;
            }

            if (is_file($currentfolder . '/' . $entry)) {
                if (is_writable($currentfolder . '/' . $entry) && unlink($currentfolder . '/' . $entry)) {
                    $result['successfiles']++;
                } else {
                    $result['failedfiles']++;
                    $result['failed'][] = str_replace($startFolder, 'cache', $currentfolder . '/' . $entry);
                }
            }

            if (is_dir($currentfolder . '/' . $entry)) {
                $this->flushHelper($startFolder, $additional . '/' . $entry, $result);

                if (@rmdir($currentfolder . '/' . $entry)) {
                    $result['successfolders']++;
                } else {
                    $result['failedfolders']++;
                }
            }
        }

        $dir->close();

        $this->updateCacheVersion();
    }

    /**
     * Write our version string out to given cache directory
     */
    private function updateCacheVersion()
    {
        $version = md5($this->boltVersion);
        file_put_contents($this->getDirectory() . '/.version', $version);
    }
}
