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
    /** @var AssetInterface[] */
    private $assets = [];
    /** @var bool */
    private $loadedAssets = false;
    /** @var  string */
    private $basePath;

    /**
     * Returns a list of assets to register. Assets can be a file, snippet, or widget.
     *
     * @return AssetInterface[]
     */
    protected function registerAssets()
    {
        return [];
    }

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendAssetServices()
    {
        $app = $this->getContainer();

        $app['asset.queue.file'] = $app->share(
            $app->extend(
                'asset.queue.file',
                function ($queue) {
                    $this->loadAssets();

                    foreach ($this->assets as $asset) {
                        if ($asset instanceof FileAssetInterface) {
                            $queue->add($asset);
                        }
                    }

                    return $queue;
                }
            )
        );

        $app['asset.queue.snippet'] = $app->share(
            $app->extend(
                'asset.queue.snippet',
                function ($queue) {
                    $this->loadAssets();

                    foreach ($this->assets as $asset) {
                        if ($asset instanceof SnippetAssetInterface) {
                            $queue->add($asset);
                        }
                    }

                    return $queue;
                }
            )
        );

        $app['asset.queue.widget'] = $app->share(
            $app->extend(
                'asset.queue.widget',
                function ($queue) {
                    $this->loadAssets();

                    foreach ($this->assets as $asset) {
                        if ($asset instanceof WidgetAssetInterface) {
                            $queue->add($asset);
                        }
                    }

                    return $queue;
                }
            )
        );
    }

    /**
     * Merges assets returned from registerAssets() to our list.
     */
    private function loadAssets()
    {
        if ($this->loadedAssets) {
            return;
        }

        foreach ($this->registerAssets() as $asset) {
            if (!$asset instanceof AssetInterface) {
                throw new \InvalidArgumentException(sprintf(
                    '%s::registerAssets() should return a list of Bolt\Asset\AssetInterface objects. Got: %s',
                    get_called_class(),
                    get_class($asset)
                ));
            }

            $this->addAsset($asset);
        }

        $this->loadedAssets = true;
    }

    /**
     * Add an file, snippet or widget asset to the render queue.
     *
     * @internal Will be made private in 4.0. Use registerAssets() instead.
     *
     * @param AssetInterface $asset
     */
    protected function addAsset(AssetInterface $asset)
    {
        $this->assets[] = $asset;
    }

    /**
     * Get the base path, that is, the directory where the (derived) extension
     * class file is located.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @return string
     */
    private function getBasePath()
    {
        if ($this->basePath === null) {
            $app = $this->getContainer();
            $reflection = new \ReflectionClass($this);
            $basePath = dirname($reflection->getFileName());
            $this->basePath = $app['pathmanager']->create($basePath);
        }

        return $this->basePath;
    }

    /**
     * Get the extensions base URL.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @return string
     */
    private function getBaseUrl()
    {
        $app = $this->getContainer();
        $extPath = $app['resources']->getPath('extensions');
        $extUrl = $app['resources']->getUrl('extensions');
        $relative = str_replace($extPath, '', $this->basePath);

        return $extUrl . ltrim($relative, '/') . '/';
    }

    /** @return Container */
    abstract protected function getContainer();
}
