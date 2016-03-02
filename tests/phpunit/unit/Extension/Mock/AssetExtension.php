<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;

/**
 * Mock extension that extends SimpleExtension for testing the AssetTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetExtension extends SimpleExtension
{
    private $assets;

    public function setAssets($assets)
    {
        $this->assets = $assets;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        return $this->assets;
    }
}
