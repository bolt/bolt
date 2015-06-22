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
}
