<?php

namespace Bolt\Asset;

/**
 * Interface for assets.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface AssetInterface
{
    /**
     * Convert object into a usable string.
     *
     * @return string
     */
    public function __toString();

    /**
     * Get the priority of the asset used for sorting.
     *
     * @return integer
     */
    public function getPriority();

    /**
     * Set the asset's priority.
     *
     * @param integer $priority
     *
     * @return AssetInterface
     */
    public function setPriority($priority);

    /**
     * Get the asset's zone. Either 'frontend' or 'backend'
     *
     * @return string|null
     */
    public function getZone();

    /**
     * Set the asset zone. Either 'frontend' or 'backend'.
     *
     * @param string $zone
     *
     * @return AssetInterface
     */
    public function setZone($zone);

    /**
     * Get the assets's target location.
     *
     * @return string|null
     */
    public function getLocation();

    /**
     * Target locational element.
     *
     * @param string $location
     *
     * @return AssetInterface
     */
    public function setLocation($location);
}
