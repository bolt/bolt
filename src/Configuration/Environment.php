<?php

namespace Bolt\Configuration;

use Bolt\Cache;
use Bolt\Composer\Action\DumpAutoload;
use Bolt\Exception\PackageManagerException;
use Pimple;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Environment set up and management class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Environment
{
    /** @var Cache */
    protected $cache;
    /** @var Pimple */
    protected $actions;
    /** @var Filesystem */
    protected $filesystem;
    /** @var string */
    protected $boltPath;
    /** @var string */
    protected $boltAssetsPath;

    /** @var string */
    protected $appPath;
    /** @var string */
    protected $viewPath;
    /** @var string */
    protected $boltVersion;

    /** @var bool */
    private $versionChange;

    /**
     * Constructor.
     *
     * @param string $boltPath
     * @param string $boltAssetsPath
     * @param Cache  $cache
     * @param Pimple $actions
     * @param string $boltVersion
     */
    public function __construct($boltPath, $boltAssetsPath, Cache $cache, Pimple $actions, $boltVersion)
    {
        $this->filesystem = new Filesystem();
        $this->boltPath = $boltPath;
        $this->boltAssetsPath = $boltAssetsPath;
        $this->cache = $cache;
        $this->actions = $actions;
        $this->boltVersion = $boltVersion;
    }

    /**
     * Has a Bolt version change been detected.
     *
     * @return bool
     */
    public function hasVersionChange()
    {
        return (bool) $this->versionChange;
    }

    /**
     * Check Bolt's version against a cached key. If there is a change we flush
     * the cache data and if required synchronise asset directories.
     */
    public function checkVersion()
    {
        if ($this->checkCacheVersion()) {
            return;
        }
        $this->syncAssets();
        $this->cache->flushAll();
        $this->updateAutoloader();
        $this->updateCacheVersion();
    }

    /**
     * Perform a synchronisation of files in specific app/view/ subdirectories.
     *
     * @return array|null
     */
    public function syncAssets()
    {
        if ($this->boltPath . '/app/view' === $this->boltAssetsPath) {
            return null;
        }

        $assetDirs = ['css', 'fonts', 'img', 'js'];
        $response = null;

        foreach ($assetDirs as $dir) {
            try {
                $this->syncAssetsDirectory($dir);
            } catch (IOException $e) {
                $response[] = $e->getMessage();
            } catch (\UnexpectedValueException $e) {
                $response[] = $e->getMessage();
            }
        }

        return $response;
    }

    /**
     * Synchronise the files in an app/view/ subdirectory.
     *
     * @param string $dir
     */
    protected function syncAssetsDirectory($dir)
    {
        $source = $this->boltPath . '/app/view/' . $dir;
        $target = $this->boltAssetsPath . '/' . $dir;

        // Mirror source and destination, overwrite existing file and clean up removed files
        $this->filesystem->mirror($source, $target, null, ['override' => true, 'delete' => true]);
    }

    /**
     * Check if the cache version matches Bolt's current version.
     *
     * @return bool TRUE  - versions match
     *              FALSE - versions don't match
     */
    protected function checkCacheVersion()
    {
        $fileName = $this->getVersionFileName();

        if (!file_exists($fileName)) {
            return false;
        }

        $version = md5($this->boltVersion);
        $cached = file_get_contents($fileName);

        if ($version === $cached) {
            return true;
        }
        $this->versionChange = true;

        return false;
    }

    /**
     * Update the extension autoloader.
     */
    protected function updateAutoloader()
    {
        $cwd = getcwd();

        try {
            /** @var DumpAutoload $autoload */
            $autoload = $this->actions['autoload'];
            $autoload->execute();
        } catch (PackageManagerException $e) {
            // Write access is potentially disabled
        }

        chdir($cwd);
    }

    /**
     * Write our version string out to given cache directory.
     */
    protected function updateCacheVersion()
    {
        $version = md5($this->boltVersion);
        file_put_contents($this->getVersionFileName(), $version);
    }

    private function getVersionFileName()
    {
        return dirname(dirname($this->cache->getDirectory())) . '/.version';
    }
}
