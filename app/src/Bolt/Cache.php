<?php

namespace Bolt;

/**
 * Simple, file based cache for volatile data.. Useful for storing non-vital
 * information like feeds, and other stuff that can be recovered easily.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 **/
class Cache extends \Doctrine\Common\Cache\FilesystemCache
{
    /**
     * @var string
     */
    private $cacheDir = "";

    /**
     *
     */
    const DEFAULT_MAX_AGE = 600; // 10 minutes

    /**
     *
     */
    const DEFAULT_EXTENSION = '.boltcache.data';

    /**
     * Set up the object. Initialize the proper folder for storing the
     * files.
     *
     * @param string $cacheDir
     */
    public function __construct($cacheDir = "")
    {
        if ($cacheDir == "") {
            $this->cacheDir = realpath(__DIR__ . "/../../cache");
        } else {
            $this->cacheDir = $cacheDir;
        }

        try {
            parent::__construct($this->cacheDir, self::DEFAULT_EXTENSION);
        } catch (\InvalidArgumentException $e) {
            echo $e->getMessage();
            die();
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
        return parent::doSave($key, $data, $lifeTime);
    }

    /**
     *
     * Get a stored value from the cache if possible. Otherwise return 'false'. If the
     * stored value was an array or object, it will be unserialized before it's returned.
     *
     * Returns false if no valid cached data was available.
     *
     * Note: If you're trying to store 'false' in the cache, the results might
     * seem a tad bit confusing. ;-)
     *
     * @param $key
     * @param bool $maxage
     * @return bool|mixed|string
     */
    public function get($key, $maxage = false)
    {
        return parent::doFetch($key);
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
        return parent::doContains($key);
    }

    /**
     * @param $key
     * @return bool
     * @deprecated
     */
    public function clear($key)
    {
        return parent::doDelete($key);
    }

    /**
     * @deprecated
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

        parent::doFlush();

        return $result;

    }
}
