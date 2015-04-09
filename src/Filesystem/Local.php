<?php
namespace Bolt\Filesystem;

use League\Flysystem\Adapter\Local as LocalBase;
use League\Flysystem\Config;
use League\Flysystem\Util;

class Local extends LocalBase
{
    const VISIBILITY_READONLY = 'readonly';

    protected static $permissions = array(
        'public'    => 0755,
        'readonly'  => 0744,
        'private'   => 0700
    );

    public function __construct($root)
    {
        $realRoot = $this->ensureDirectory($root);
        $this->setPathPrefix($realRoot);
    }

    protected function ensureDirectory($root)
    {
        if (!is_dir($root) && !@mkdir($root, 0755, true)) {
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

    public function update($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $mimetype = Util::guessMimeType($path, $contents);

        if (!is_writable($location)) {
            return false;
        }

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        return compact('path', 'size', 'contents', 'mimetype');
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

    /**
     * Get the normalized path from a SplFileInfo object.
     *
     * @param \SplFileInfo $file
     *
     * @return string
     */
    protected function getFilePath(\SplFileInfo $file)
    {
        $path = parent::getFilePath($file);
        if ($this->pathSeparator === '\\') {
            return str_replace($this->pathSeparator, '/', $path);
        } else {
            return $path;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        $location = $this->applyPathPrefix($path);
        clearstatcache(false, $location);
        if ($this->userCanWrite($location) || $this->groupCanWrite($location)) {
            $visibility = self::VISIBILITY_PUBLIC;
        } elseif ($this->userCanRead($location) || $this->groupCanRead($location)) {
            $visibility = self::VISIBILITY_READONLY;
        } else {
            $visibility = self::VISIBILITY_PRIVATE;
        }

        return compact('visibility');
    }

    protected function userCanWrite($location)
    {
        $worldPermissions = substr(sprintf('%o', fileperms($location)), -1, 1);
        if ($worldPermissions >= 6) {
            return true;
        }

        $permissions = substr(sprintf('%o', fileperms($location)), -3, 1);
        $fileOwnerId = fileowner($location);
        
        if (function_exists('posix_getuid')) {
            $uhandler = 'posix_getuid';
        } else {
            $uhandler = 'getmyuid';
        }
        
        $procOwnerId = call_user_func($uhandler);
        
        if ($fileOwnerId === $procOwnerId && (int) $permissions >= 6) {
            return true;
        }

        return false;
    }

    protected function groupCanWrite($location)
    {
        $permissions = substr(sprintf('%o', fileperms($location)), -2, 1);
        $fileOwnerGroup = filegroup($location);
        
        if (function_exists('posix_getgid')) {
            $ghandler = 'posix_getgid';
        } else {
            $ghandler = 'getmygid';
        }
        
        $procOwnerGroup = call_user_func($ghandler);
        if ($fileOwnerGroup === $procOwnerGroup && (int) $permissions >= 6) {
            return true;
        }

        return false;
    }

    protected function userCanRead($location)
    {
        $worldPermissions = substr(sprintf('%o', fileperms($location)), -1);
        if ($worldPermissions >= 5) {
            return true;
        }

        $permissions = substr(sprintf('%o', fileperms($location)), -3, 1);
        $fileOwnerId = fileowner($location);

        if (function_exists('posix_getuid')) {
            $uhandler = 'posix_getuid';
        } else {
            $uhandler = 'getmyuid';
        }
        
        $procOwnerId = call_user_func($uhandler);
        
        if ($fileOwnerId === $procOwnerId && (int) $permissions >= 5) {
            return true;
        }

        return false;
    }

    protected function groupCanRead($location)
    {
        $permissions = substr(sprintf('%o', fileperms($location)), -2, 1);
        $fileOwnerGroup = filegroup($location);

        if (function_exists('posix_getgid')) {
            $ghandler = 'posix_getgid';
        } else {
            $ghandler = 'getmygid';
        }
        
        $procOwnerGroup = call_user_func($ghandler);
        
        if ($fileOwnerGroup === $procOwnerGroup && (int) $permissions >= 5) {
            return true;
        }

        return false;
    }
}
