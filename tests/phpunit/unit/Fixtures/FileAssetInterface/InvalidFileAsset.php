<?php

namespace Bolt\Tests\Fixtures\FileAssetInterface;

use Bolt\Asset\File\FileAssetBase;

/**
 * Test fixture for an invalid FileAssetInterface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class InvalidFileAsset extends FileAssetBase
{
    protected $type = 'invalid';

    public function __toString()
    {
    }
}
