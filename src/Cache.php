<?php

namespace Bolt;

use Bolt\Configuration\ResourceManager;
use Doctrine\Common\Cache\FilesystemCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Simple, file based cache for volatile data.. Useful for storing non-vital
 * information like feeds, and other stuff that can be recovered easily.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 */
class Cache extends FilesystemCache
{

    /**
     * Max cache age. Default 10 minutes
     */
    const DEFAULT_MAX_AGE = 600;

    /**
     * Default cache file extension
     */
    const DEFAULT_EXTENSION = '.boltcache.data';

    /**
     * Set up the object. Initialize the proper folder for storing the
     * files.
     *
     * @param  string                               $cacheDir
     * @throws \Exception|\InvalidArgumentException
     */
    public function __construct($cacheDir = null)
    {
        $filesystem = new Filesystem();
        if (!$filesystem->isAbsolutePath($cacheDir)) {
            $cacheDir = realpath(__DIR__ . "/" . $cacheDir);
        }

        try {
            parent::__construct($cacheDir, self::DEFAULT_EXTENSION);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * Clear the cache. Both the doctrine FilesystemCache, as well as twig and thumbnail temp files.
     *
     * @see clearCacheHelper
     *
     */
    public function clearCache()
    {
        $result = array(
            'successfiles' => 0,
            'failedfiles' => 0,
            'failed' => array(),
            'successfolders' => 0,
            'failedfolders' => 0,
            'log' => ''
        );

        // Clear Doctrine's folder.
        parent::flushAll();

        // Clear our own cache folder.
        $this->clearCacheHelper($this->getDirectory(), '', $result);

        // Clear the thumbs folder.
        $app = ResourceManager::getApp();
        $this->clearCacheHelper($app['resources']->getPath('web') . '/thumbs', '', $result);

        return $result;
    }

    /**
     * Helper function for clearCache()
     *
     * @param string $startFolder
     * @param string $additional
     * @param array  $result
     */
    private function clearCacheHelper($startFolder, $additional, &$result)
    {
        $currentfolder = realpath($startFolder . "/" . $additional);

        if (!file_exists($currentfolder)) {
            $result['log'] .= "Folder $currentfolder doesn't exist.<br>";

            return;
        }

        $dir = dir($currentfolder);

        while (($entry = $dir->read()) !== false) {

            $exclude = array('.', '..', 'index.html', '.gitignore');

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

                $this->clearCacheHelper($startFolder, $additional . '/' . $entry, $result);

                if (@rmdir($currentfolder . '/' . $entry)) {
                    $result['successfolders']++;
                } else {
                    $result['failedfolders']++;
                }

            }

        }

        $dir->close();
    }
}
