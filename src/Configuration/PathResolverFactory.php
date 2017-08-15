<?php

namespace Bolt\Configuration;

/**
 * This acts as a staging area for PathResolver so that it doesn't have to be created until it is needed.
 * It is basically only to bridge ResourceManager to PathResolver.
 *
 * @deprecated since 3.3, will be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class PathResolverFactory
{
    /** @var string */
    private $rootPath;

    /** @var array */
    private $paths;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->paths = PathResolver::defaultPaths();
    }

    /**
     * @param string $rootPath
     *
     * @return PathResolverFactory
     */
    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasRootPath()
    {
        return (bool) $this->rootPath;
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * @param array $paths
     *
     * @return PathResolverFactory
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param array $paths
     *
     * @return PathResolverFactory
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_replace($this->paths, $paths);

        return $this;
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Create PathResolver.
     *
     * @return PathResolver
     */
    public function create()
    {
        if ($this->rootPath === null) {
            throw new \LogicException('Root path must be set before PathResolver can be created');
        }

        return new PathResolver($this->rootPath, $this->paths);
    }
}
