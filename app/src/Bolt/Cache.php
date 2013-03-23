<?php

namespace Bolt;

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
     * @param string $cacheDir
     * @throws \Exception|\InvalidArgumentException
     */
    public function __construct($cacheDir = "")
    {
        if ($cacheDir == "") {
            $cacheDir = realpath(__DIR__ . "/../../cache");
        } else {
            // We don't have $app here, so we use the filesystem component
            // directly here.
            $filesystem = new Filesystem();
            if (!$filesystem->isAbsolutePath($cacheDir)){
                $cacheDir = realpath(__DIR__ . "/" . $cacheDir);
            }
        }

        try {
            parent::__construct($cacheDir, self::DEFAULT_EXTENSION);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     *
     * Set a value in the cache. If $data is an array or an object it's
     * serialised.
     *
     * Note: only store objects that actually _can_ be serialized and unserialized
     *
     * @param $key
     * @param $data
     * @param int $lifeTime
     * @return bool|int
     * @deprecated
     */
    public function set($key, $data, $lifeTime = self::DEFAULT_MAX_AGE)
    {
        return parent::save($key, $data, $lifeTime);
    }

    /**
     *
     * Get a stored value from the cache if possible. Otherwise return 'false'. If the
     * stored value was an array or object, it will NOT be unserialized before it's returned.
     *
     * Returns false if no valid cached data was available.
     *
     * Note: If you're trying to store 'false' in the cache, the results might
     * seem a tad bit confusing. ;-)
     *
     * @param $key
     * @param bool $maxage
     * @return bool|mixed|string
     * @deprecated
     */
    public function get($key, $maxage = false)
    {
        $result = parent::fetch($key);

        return $result;
    }

    /**
     *
     * Check if a given key is cached, and not too old.
     *
     * @param $key
     * @param $maxage
     * @return bool
     * @deprecated
     */
    public function isvalid($key, $maxage)
    {
        return parent::contains($key);
    }

    /**
     * @param $key
     * @return bool
     * @deprecated
     */
    public function clear($key)
    {
        return parent::delete($key);
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

        parent::flushAll();

        $this->clearCacheHelper('', $result);

        return $result;

    }

    /**
     * Helper function for clearCache()
     * @param string $additional
     * @param array $result
     */
    private function clearCacheHelper($additional, &$result)
    {

        $currentfolder = realpath($this->getDirectory() . "/" . $additional);

        if (!file_exists($currentfolder)) {
            $result['log'] .= "Folder $currentfolder doesn't exist.<br>";

            return;
        }

        $d = dir($currentfolder);

        while (false !== ($entry = $d->read())) {

            if ($entry == "." || $entry == ".." || $entry == "index.html" || $entry == '.gitignore') {
                continue;
            }

            if (is_file($currentfolder."/".$entry)) {
                if (is_writable($currentfolder."/".$entry) && unlink($currentfolder."/".$entry)) {
                    $result['successfiles']++;
                } else {
                    $result['failedfiles']++;
                    $result['failed'][] = str_replace($this->getDirectory(), "cache", $currentfolder."/".$entry);
                }
            }

            if (is_dir($currentfolder."/".$entry)) {

                $this->clearCacheHelper($additional."/".$entry, $result);

                if (@rmdir($currentfolder."/".$entry)) {
                    $result['successfolders']++;
                } else {
                    $result['failedfolders']++;
                }

            }

        }

        $d->close();

    }

}
