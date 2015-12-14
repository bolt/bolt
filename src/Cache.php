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
    /** @var string[] replacements for disallowed file characters */
    private $replacementCharacters = ['__', '-'];
    /** @var string[] regular expressions for replacing disallowed characters in file name */
    private $disallowedCharacterPatterns = [
        '/\-/', // replaced to disambiguate original `-` and `-` derived from replacements
        '/[^a-zA-Z0-9\-_\[\]]/', // also excludes non-ascii chars (not supported, depending on FS),
    ];

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
            . self::EXTENSION;
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
