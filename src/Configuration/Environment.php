<?php

namespace Bolt\Configuration;

use Bolt\Cache;
use Silex\Application;
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
    /** @var Filesystem */
    protected $filesystem;
    /** @var string */
    protected $srcRoot;
    /** @var string */
    protected $webRoot;
    /** @var string */
    protected $boltName;
    /** @var string */
    protected $boltVersion;

    /**
     * Constructor.
     *
     * @param ResourceManager $resourceManager
     */
    public function __construct(Application $app)
    {
        $this->filesystem = new Filesystem();
        $this->srcRoot = realpath($app['resources']->getPath('root'));
        $this->webRoot = realpath($app['resources']->getPath('web'));
        $this->cache = $app['cache'];
        $this->boltName = $app['bolt_name'];
        $this->boltVersion = $app['bolt_version'];
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
        $this->cache->clearCache();
    }

    /**
     * Perform a synchronisation of files in specific app/view/ subdirectories.
     */
    public function syncView()
    {
        $views = ['css', 'fonts', 'img', 'js'];
        foreach ($views as $dir) {
            $this->syncViewDirectory($dir);
        }
    }

    /**
     * Synchronise the files in an app/view/ subdirectory.
     *
     * @param string $dir
     */
    protected function syncViewDirectory($dir)
    {
        if ($this->srcRoot === $this->webRoot) {
            return;
        }

        $source = $this->srcRoot . '/app/view/' . $dir;
        $target = $this->webRoot . '/app/view/' . $dir;
        if ($this->filesystem->exists($target)) {
            return;
        }

        try {
            $this->filesystem->mirror($source, $target, null, ['override' => true, 'delete' => true]);
        } catch (IOException $e) {
            // Apparently not.
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
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

        $version = md5($this->boltVersion . $this->boltName);
        $cached  = file_get_contents($file);

        if ($version === $cached) {
            return true;
        }

        return false;
    }
}
