<?php

namespace Bolt\Extension;

use Bolt\Asset\AssetInterface;
use Bolt\Asset\File\FileAssetInterface;
use Bolt\Asset\Snippet\SnippetAssetInterface;
use Bolt\Asset\Widget\WidgetAssetInterface;
use Pimple as Container;

/**
 * Asset loading trait for an extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait AssetTrait
{
    /** @return Container */
    abstract protected function getContainer();

    /**
     * Add an file, snippet or widget asset to the render queue.
     *
     * @param AssetInterface $asset
     */
    protected function addAsset(AssetInterface $asset)
    {
        $app = $this->getContainer();

        if ($asset instanceof FileAssetInterface) {
            $app['asset.queue.file']->add($asset);
        } elseif ($asset instanceof SnippetAssetInterface) {
            $app['asset.queue.snippet']->add($asset);
        } elseif ($asset instanceof WidgetAssetInterface) {
            $app['asset.queue.widget']->add($asset);
        } else {
            throw new \InvalidArgumentException('Asset must implement either FileAssetInterface, SnippetAssetInterface or WidgetAssetInterface');
        }
    }
}
