<?php

namespace Bolt;

use Bolt\Configuration\ResourceManager;
use Doctrine\Common\Cache\FilesystemCache;

/**
 * Simple, file based cache for volatile data.. Useful for storing non-vital
 * information like feeds, and other stuff that can be recovered easily.
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Cache extends FilesystemCache
{
    /**
     * Max cache age. Default 10 minutes.
     */
    const DEFAULT_MAX_AGE = 600;

    /** @var Application */
    private $app;

    /**
     * Default cache file extension.
     */
    private $extension = '.data';

    /**
     * @var string[] regular expressions for replacing disallowed characters in file name
     */
    private $disallowedCharacterPatterns = array(
        '/\-/', // replaced to disambiguate original `-` and `-` derived from replacements
        '/[^a-zA-Z0-9\-_\[\]]/' // also excludes non-ascii chars (not supported, depending on FS)
    );

    /**
     * @var string[] replacements for disallowed file characters
     */
    private $replacementCharacters = array('__', '-');

    /**
     * Set up the object. Initialize the proper folder for storing the files.
     *
     * @param string      $cacheDir
     * @param Application $app
     *
     * @throws \Exception
     */
    public function __construct($cacheDir, Application $app)
    {
        $this->app = $app;

        try {
            parent::__construct($cacheDir, $this->extension);

            // If the Bolt version has changed, flush our cache
            if (!$this->checkCacheVersion()) {
                $this->clearCache();
            }
        } catch (\Exception $e) {
            $app['logger.system']->critical($e->getMessage(), array('event' => 'exception', 'exception' => $e));
            throw $e;
        }
    }

    /**
     * Generate a filename for the cached items in our filebased cache.
     *
     * The original Doctrine/cache function stored files in folders that
     * were nested 32 layers deep. In practice this led to cache folders
     * containing up to 600,000 folders, while containing only about 15,000
     * cached items. This is a huge overkill. Here, we use only two levels,
     * which still means each folder will in practice contain only a very
     * limited amount of files. i.e.: for 15,000 files, there are 256*256
     * folders, which statstically means one or two files per folder.
     *
     * @param string $id
     *
     * @return string
     */
    protected function getFilename($id)
    {
        $foldername = implode(str_split(substr(hash('sha256', $id), 0, 4), 2), DIRECTORY_SEPARATOR);

        return $this->directory
            . DIRECTORY_SEPARATOR
            . $foldername
            . DIRECTORY_SEPARATOR
            . preg_replace($this->disallowedCharacterPatterns, $this->replacementCharacters, $id)
            . $this->extension;
    }

    /**
     * Clear the cache. Both the doctrine FilesystemCache, as well as twig and thumbnail temp files.
     *
     * @see clearCacheHelper
     */
    public function clearCache()
    {
        $result = array(
            'successfiles'   => 0,
            'failedfiles'    => 0,
            'failed'         => array(),
            'successfolders' => 0,
            'failedfolders'  => 0,
            'log'            => ''
        );

        // Clear Doctrine's folder.
        $this->flushAll();

        // Clear our own cache folder.
        $this->clearCacheHelper($this->getDirectory(), '', $result);

        // Clear the thumbs folder.
        $app = ResourceManager::getApp();
        $this->clearCacheHelper($app['resources']->getPath('web') . '/thumbs', '', $result);

        return $result;
    }

    /**
     * Helper function for clearCache().
     *
     * @param string $startFolder
     * @param string $additional
     * @param array  $result
     */
    private function clearCacheHelper($startFolder, $additional, &$result)
    {
        $currentfolder = realpath($startFolder . '/' . $additional);

        if (!file_exists($currentfolder)) {
            $result['log'] .= "Folder $currentfolder doesn't exist.<br>";

            return;
        }

        $dir = dir($currentfolder);

        while (($entry = $dir->read()) !== false) {
            $exclude = array('.', '..', 'index.html', '.gitignore', '.version');

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

        $this->updateCacheVersion();
    }

    /**
     * Check if the cache version matches Bolt's current version
     *
     * @return boolean TRUE  - versions match
     *                 FALSE - versions don't match
     */
    private function checkCacheVersion()
    {
        $file = $this->getDirectory() . '/.version';

        if (!file_exists($file)) {
            return false;
        }

        $version = md5($this->app['bolt_version'].$this->app['bolt_name']);
        $cached  = file_get_contents($file);

        if ($version === $cached) {
            return true;
        }

        return false;
    }

    /**
     * Write our version string out to given cache directory
     */
    private function updateCacheVersion()
    {
        $version = md5($this->app['bolt_version'].$this->app['bolt_name']);
        file_put_contents($this->getDirectory() . '/.version', $version);
    }
}
