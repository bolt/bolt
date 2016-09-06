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
    protected $rootPath;
    /** @var string */
    protected $appPath;
    /** @var string */
    protected $viewPath;
    /** @var string */
    protected $boltVersion;

    /**
     * Constructor.
     *
     * @param string $appPath
     * @param string $viewPath
     * @param Cache  $cache
     * @param Pimple $actions
     * @param string $boltVersion
     */
    public function __construct($appPath, $viewPath, Cache $cache, Pimple $actions, $boltVersion)
    {
        $this->filesystem = new Filesystem();
        $this->appPath = rtrim($appPath, '/');
        $this->viewPath = rtrim($viewPath, '/');
        $this->cache = $cache;
        $this->actions = $actions;
        $this->boltVersion = $boltVersion;
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
        $this->syncView();
        $this->cache->flushAll();
        $this->updateAutoloader();
        $this->updateCacheVersion();
    }

    /**
     * Perform a synchronisation of files in specific app/view/ subdirectories.
     *
     * @return array|null
     */
    public function syncView()
    {
        $views = ['css', 'fonts', 'img', 'js'];
        $response = null;
        foreach ($views as $dir) {
            try {
                $this->syncViewDirectory($dir);
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
    protected function syncViewDirectory($dir)
    {
        if ($this->viewPath === $this->appPath . '/view') {
            return;
        }

        $source = $this->appPath . '/view/' . $dir;
        $target = $this->viewPath . '/' . $dir;

        // Mirror source and destination, overwrite existing file and clean up removed files
        $this->filesystem->mirror($source, $target, null, ['override' => true, 'delete' => true]);
    }

    /**
     * Check if the cache version matches Bolt's current version
     *
     * @return boolean TRUE  - versions match
     *                 FALSE - versions don't match
     */
    protected function checkCacheVersion()
    {
        $file = $this->cache->getDirectory() . '/.version';

        if (!file_exists($file)) {
            return false;
        }

        $version = md5($this->boltVersion);
        $cached  = file_get_contents($file);

        if ($version === $cached) {
            return true;
        }

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
     * Write our version string out to given cache directory
     */
    protected function updateCacheVersion()
    {
        $version = md5($this->boltVersion);
        file_put_contents($this->cache->getDirectory() . '/.version', $version);
    }
}
