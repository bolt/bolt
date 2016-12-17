<?php

namespace Bolt\Twig;

use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Twig_Error_Loader as LoaderError;
use Twig_Source as TwigSource;

/**
 * Loads templates from a Bolt\Filesystem interface.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemLoader extends \Twig_Loader_Filesystem
{
    /** @var FilesystemInterface */
    protected $filesystem;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem The filesystem to use
     * @param array|string        $paths      A path or an array of paths where to look for templates
     */
    public function __construct(FilesystemInterface $filesystem, $paths = [])
    {
        $this->filesystem = $filesystem;
        parent::__construct($paths);
    }

    /**
     * {@inheritdoc}
     */
    public function addPath($path, $namespace = self::MAIN_NAMESPACE)
    {
        $this->addDir($this->filesystem->getDir($path), $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function prependPath($path, $namespace = self::MAIN_NAMESPACE)
    {
        $this->prependDir($this->filesystem->getDir($path), $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext($name)
    {
        $file = $this->findTemplate($name);

        return new TwigSource($file->read(), $name);
    }

    /**
     * Adds a directory where templates are stored.
     *
     * @param DirectoryInterface $dir
     * @param string             $namespace
     *
     * @throws LoaderError
     */
    public function addDir(DirectoryInterface $dir, $namespace = self::MAIN_NAMESPACE)
    {
        // invalidate the cache
        $this->cache = $this->errorCache = [];

        if (!$dir->exists()) {
            throw new LoaderError(sprintf('The "%s" directory does not exist.', $dir->getFullPath()));
        }
        if (!$dir->isDir()) {
            throw new LoaderError(sprintf('The path "%s" is not a directory.', $dir->getFullPath()));
        }

        $this->paths[$namespace][] = $dir;
    }

    /**
     * Prepends a directory where templates are stored.
     *
     * @param DirectoryInterface $dir
     * @param string             $namespace
     *
     * @throws LoaderError
     */
    public function prependDir(DirectoryInterface $dir, $namespace = self::MAIN_NAMESPACE)
    {
        // invalidate the cache
        $this->cache = $this->errorCache = [];

        if (!$dir->exists()) {
            throw new LoaderError(sprintf('The "%s" directory does not exist.', $dir->getFullPath()));
        }
        if (!$dir->isDir()) {
            throw new LoaderError(sprintf('The path "%s" is not a directory.', $dir->getFullPath()));
        }

        if (!isset($this->paths[$namespace])) {
            $this->paths[$namespace][] = $dir;
        } else {
            array_unshift($this->paths[$namespace], $dir);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        return $this->findTemplate($name)->read();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $this->findTemplate($name)->getFullPath();
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        try {
            $timestamp = $this->findTemplate($name)->getTimestamp();

            return $timestamp <= $time;
        } catch (ExceptionInterface $e) {
            return false;
        }
    }

    /**
     * Finds a file given the template name.
     *
     * @param string $name  The template name.
     * @param bool   $throw Whether to throw exceptions or return false.
     *
     * @throws LoaderError
     *
     * @return FileInterface|false
     */
    protected function findTemplate($name, $throw = true)
    {
        $name = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->errorCache[$name])) {
            if (!$throw) {
                return false;
            }

            throw new LoaderError($this->errorCache[$name]);
        }

        $this->validateName($name);

        list($namespace, $shortName) = $this->parseName($name);

        if (!isset($this->paths[$namespace])) {
            $this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);

            if (!$throw) {
                return false;
            }

            throw new LoaderError($this->errorCache[$name]);
        }

        foreach ($this->paths[$namespace] as $dir) {
            /** @var DirectoryInterface $dir */
            try {
                $file = $dir->getFile($shortName);
            } catch (ExceptionInterface $e) {
                continue;
            }
            if ($file->exists()) {
                return $this->cache[$name] = $file;
            }
        }

        $paths = array_map(
            function (DirectoryInterface $dir) {
                return $dir->getFullPath();
            },
            $this->paths[$namespace]
        );
        $paths = implode(', ', $paths);
        $this->errorCache[$name] = sprintf('Unable to find template "%s" (looked into: %s).', $name, $paths);

        if (!$throw) {
            return false;
        }

        throw new LoaderError($this->errorCache[$name]);
    }
}
