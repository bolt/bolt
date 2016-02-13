<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;

/**
 * Mock extension that extends SimpleExtension for testing the ConfigTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigExtension extends SimpleExtension
{
    private $assets;

    protected function getDefaultConfig()
    {
        return [
            'blame' => 'koala',
        ];
    }

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
