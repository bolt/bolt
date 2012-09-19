<?php


/**
 * Simple, file based cache for volatile data.. Useful for storing non-vital
 * information like feeds, and other stuff that can be recoverd easily.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 **/
class Cache {


    private $dir = "";
    private $maxage = 600; // 10 minutes

    /**
     * Set up the object. Initialize the propr folder for storing the
     * files.
     */
    public function __construct()
    {

        $this->dir = realpath(__DIR__ . "/../cache");

        if (!is_writable($this->dir)) {
            // TODO: log a warning here..
        }

    }

    /**
     *
     * Set a value in the cache. If $data is an array or an object it's
     * serialised.
     *
     * Note: only store objects that actually _can_ be serialized and unseralized
     *
     * @param $key
     * @param $data
     */
    function set($key, $data)
    {

        $filename = $this->getFilename($key);

        if (is_array($data) || is_object($data)) {
            $data = serialize($data);
        }

        file_put_contents($filename, $data);

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
     * @param int $maxage Maximum age of cache in seconds.
     * @return bool|mixed|string
     */
    function get($key, $maxage = false)
    {

        $filename = $this->getFilename($key);

        // No file, we can stop..
        if (!file_exists($filename)) {
            return false;
        }

        $age = date("U") - filectime($filename);

        if (empty($maxage)) {
            $maxage = $this->maxage;
        }

        if ($age < $maxage) {
            $data = file_get_contents($filename);

            $unserdata = @unserialize($data);

            if ($unserdata !== false || $data === 'b:0;') {
                return $unserdata;
            } else {
                return $data;
            }

        } else {
            return false;
        }

    }

    /**
     *
     * Check if a given key is cached, and not too old.
     *
     * @param $key
     * @param $maxage
     * @return bool
     */
    function isvalid($key, $maxage)
    {

        $filename = $this->getFilename($key);

        // No file, we can stop..
        if (!file_exists($filename)) {
            return false;
        }

        $age = date("U") - filectime($filename);

        if (empty($maxage)) {
            $maxage = $this->maxage;
        }

        return ($age < $maxage);

    }


    function clear($key)
    {
        // TODO: clear a certain cached value.
    }

    function clearCache()
    {
        // TODO: clear all cached values.
    }

    private function getFilename($key)
    {
        return sprintf("%s/c_%s.cache", $this->dir, substr(md5($key),0,18));
    }

}

