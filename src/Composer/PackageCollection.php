<?php

namespace Bolt\Composer;

use JsonSerializable;

/**
 * Package collection class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class PackageCollection implements JsonSerializable
{
    /** @var Package[] */
    protected $packages = [];

    /**
     * Add a package to the collection.
     *
     * @param Package $package
     */
    public function add(Package $package)
    {
        $name = $package->getName();
        if (isset($this->packages[$name])) {
            return;
        }

        $this->packages[$name] = $package;
    }

    /**
     * Get a package from the collection.
     *
     * @param string $name
     *
     * @return Package
     */
    public function get($name)
    {
        if (isset($this->packages[$name])) {
            return $this->packages[$name];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        ksort($this->packages);

        return $this->packages;
    }
}
