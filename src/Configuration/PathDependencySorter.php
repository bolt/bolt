<?php

namespace Bolt\Configuration;

use Bolt\Exception\PathResolutionException;

/**
 * Sorts PathResolver paths based on their dependencies.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class PathDependencySorter
{
    /** @var PathResolver */
    private $resolver;
    /** @var array */
    private $resolving = [];

    /**
     * Constructor.
     *
     * @param PathResolver $resolver
     */
    public function __construct(PathResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Returns the list of path names sorted by least dependent first.
     *
     * @return string[]
     */
    public function getSortedNames()
    {
        $sorted = [];
        $toSort = $this->getDependencies();

        while (count($toSort) > 0) {
            foreach ($toSort as $name => $dependencies) {
                if (!array_diff($dependencies, $sorted)) {
                    $sorted[] = $name;
                    unset($toSort[$name]);
                }
            }
        }

        return $sorted;
    }

    /**
     * Returns path names with their dependencies.
     *
     * @return array [name => dependencies]
     */
    private function getDependencies()
    {
        $dependencies = [];

        foreach ($this->resolver->names() as $name) {
            $dependencies[$name] = $this->getDependenciesRecursive($name);
        }

        return $dependencies;
    }

    /**
     * Returns dependencies recursively for a path.
     *
     * @param string $path
     * @param array  $dependencies
     *
     * @return array
     */
    private function getDependenciesRecursive($path, $dependencies = [])
    {
        $raw = $this->resolver->raw($path);
        if ($raw !== null) {
            $path = $raw;
        }

        preg_match_all('#%([^/\\\\]+)%#', $path, $matches);
        foreach ($matches[1] as $alias) {
            if (!in_array($alias, $dependencies, true)) {
                $dependencies[] = $alias;
            }

            if (isset($this->resolving[$alias])) {
                throw new PathResolutionException('Failed to resolve path dependencies. Infinite recursion detected.');
            }

            $this->resolving[$alias] = true;
            try {
                $dependencies = $this->getDependenciesRecursive($alias, $dependencies);
            } finally {
                unset($this->resolving[$alias]);
            }
        }

        return $dependencies;
    }
}
