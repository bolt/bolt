<?php

namespace Bolt\Asset;

/**
 * Trait for handling queue priority sorting.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait AssetSortTrait
{
    /**
     * Do a Schwartzian Transform for stable sort
     *
     * @see http://en.wikipedia.org/wiki/Schwartzian_transform
     *
     * @param AssetInterface[] $assets
     *
     * @return AssetInterface[]
     */
    protected function sort(array $assets)
    {
        array_walk(
            $assets,
            function (&$v, $k) {
                $v = [$v->getPriority(), $k, $v];
            }
        );

        sort($assets);

        array_walk(
            $assets,
            function (&$v) {
                $v = $v[2];
            }
        );

        return $assets;
    }
}
