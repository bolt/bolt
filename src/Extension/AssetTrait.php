<?php

namespace Bolt\Extension;

use Bolt\Asset\AssetInterface;
use Bolt\Asset\File\FileAssetInterface;
use Bolt\Asset\Snippet\SnippetAssetInterface;
use Bolt\Asset\Widget\WidgetAssetInterface;

/**
 * Asset loading trait for an extension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait AssetTrait
{
    /** @return \Silex\Application */
    abstract protected function getApp();

    /**
     * Add an file, snippet or widget asset to the render queue.
     *
     * @param AssetInterface $asset
     */
    protected function addAsset(AssetInterface $asset)
    {
        if ($asset instanceof FileAssetInterface) {
            $this->getApp()['asset.queue.file']->add($asset);
        } elseif ($asset instanceof SnippetAssetInterface) {
            $this->getApp()['asset.queue.snippet']->add($asset);
        } elseif ($asset instanceof WidgetAssetInterface) {
            $this->getApp()['asset.queue.widget']->add($asset);
        } else {
            throw new \InvalidArgumentException('Asset must implement either FileAssetInterface, SnippetAssetInterface or WidgetAssetInterface');
        }
    }
}
