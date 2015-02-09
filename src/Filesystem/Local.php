<?php
namespace Bolt\Filesystem;

use League\Flysystem\Adapter\Local as LocalBase;
use League\Flysystem\Config;
use League\Flysystem\Util;

class Local extends LocalBase
{
    public function __construct($root)
    {
        $realRoot = $this->ensureDirectory($root);
        $this->setPathPrefix($realRoot);
    }

    protected function ensureDirectory($root)
    {
        if (is_dir($root) === false || !is_writable($root)) {
            return false;
        }

        return realpath($root);
    }

    public function write($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        if (!$this->ensureDirectory(dirname($location))) {
            return false;
        }

        return parent::write($path, $contents, $config);
    }

    public function writeStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        if (!$this->ensureDirectory(dirname($location))) {
            return false;
        }

        return parent::writeStream($path, $resource, $config);
    }

    public function rename($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        $parentDirectory = $this->applyPathPrefix(Util::dirname($newpath));
        if (!$this->ensureDirectory($parentDirectory)) {
            return false;
        }

        return rename($location, $destination);
    }

    public function copy($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        if (!$this->ensureDirectory(dirname($destination))) {
            return false;
        }

        return copy($location, $destination);
    }

    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        if (!is_writable($location)) {
            return false;
        }

        return unlink($location);
    }

    public function createDir($dirname, Config $config)
    {
        $location = $this->applyPathPrefix($dirname);

        // mkdir recursively creates directories.
        // It's easier to ignore errors and check result
        // than try to recursively check for file permissions
        if (!is_dir($location) && !@mkdir($location, 0777, true)) {
            return false;
        }

        return array('path' => $dirname, 'type' => 'dir');
    }

    public function deleteDir($dirname)
    {
        $location = $this->applyPathPrefix($dirname);
        if (!is_dir($location) || !is_writable($location)) {
            return false;
        }

        return parent::deleteDir($dirname);
    }
}
