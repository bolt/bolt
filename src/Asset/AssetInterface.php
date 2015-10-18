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
}
