<?php

namespace Bolt;

/**
 * Simple, file based cache for volatile data.. Useful for storing non-vital
 * information like feeds, and other stuff that can be recoverd easily.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 **/
class Cache
{
    private $dir = "";
    private $maxage = 600; // 10 minutes

    /**
     * Set up the object. Initialize the propr folder for storing the
     * files.
     */
    public function __construct($cacheDir = "")
    {
        if ($cacheDir == ""){
            // Default
            $this->dir = realpath(__DIR__ . "/../../cache");
        }
        else {
            $this->dir = $cacheDir;
        }

        if (!is_writable($this->dir)) {
            // simple warning + die here. This shouldn't occur in practice, as it's
            // already checked in lowlevelchecks.php
            echo "<p>cache folder isn't writable. Please fix this.</p>";
            die();
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
    public function set($key, $data)
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
     * @param  int               $maxage Maximum age of cache in seconds.
     * @return bool|mixed|string
     */
    public function get($key, $maxage = false)
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
    public function isvalid($key, $maxage)
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

    public function clear($key)
    {
        // @todo clear a certain cached value.
    }



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

        $this->clearCacheHelper('', $result);

        return $result;

    }


    private function clearCacheHelper($additional, &$result)
    {

        $currentfolder = realpath($this->dir."/".$additional);

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
                    $result['failed'][] = str_replace($this->dir, "cache", $currentfolder."/".$entry);
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

    private function getFilename($key)
    {
        return sprintf("%s/c_%s.cache", $this->dir, substr(md5($key), 0, 18));
    }
}
