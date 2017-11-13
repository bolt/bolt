<?php

namespace Bolt\Tests\Fixtures\AssetSortTrait;

use Bolt\Asset\AssetSortTrait;

/**
 * Test fixture for AssetSortTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetSort
{
    use AssetSortTrait;

    public function doSort($assets)
    {
        return $this->sort($assets);
    }
}
