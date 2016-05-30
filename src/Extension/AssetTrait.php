<?php

namespace Bolt\Extension;

use Bolt\Asset\AssetInterface;
use Bolt\Asset\File\FileAssetInterface;
use Bolt\Asset\Snippet\SnippetAssetInterface;
use Bolt\Asset\Widget\WidgetAssetInterface;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Pimple as Container;
use Silex\Application;

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
        /** @var Application $app */
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

        foreach ((array) $this->registerAssets() as $asset) {
            if (!$asset instanceof AssetInterface) {
                throw new \InvalidArgumentException(sprintf(
                    '%s::registerAssets() should return a list of Bolt\Asset\AssetInterface objects. Got: %s',
                    get_called_class(),
                    is_object($asset) ? get_class($asset) : gettype($asset)
                ));
            }

            $this->addAsset($asset);
        }

        $this->loadedAssets = true;
    }

    /**
     * Add an file, snippet or widget asset to the render queue.
     *
     * @param AssetInterface $asset
     */
    private function addAsset(AssetInterface $asset)
    {
        if ($asset instanceof FileAssetInterface) {
            $this->normalizeAsset($asset);
        }
        $this->assets[] = $asset;
    }
    /**
     * Add jQuery to the output.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    protected function addJquery()
    {
        $this->getContainer()['config']->set('general/add_jquery', true);
    }

    /**
     * Don't add jQuery to the output.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    protected function disableJquery()
    {
        $this->getContainer()['config']->set('general/add_jquery', false);
    }

    /**
     * Normalizes the path and package name of the asset file.
     *
     * @param FileAssetInterface $asset
     */
    private function normalizeAsset(FileAssetInterface $asset)
    {
        $path = $asset->getPath();
        if ($path === null) {
            throw new \RuntimeException('Extension file assets must have a path set.');
        }

        // Any external resource does not need further normalisation
        if (parse_url($path, PHP_URL_HOST) !== null) {
            return;
        }

        $file = $this->getWebDirectory()->getFile($asset->getPath());
        if ($file->exists()) {
            $asset->setPackageName('extensions')->setPath($file->getPath());

            return;
        }

        $app = $this->getContainer();

        if ($app['filesystem']->has(sprintf('theme://%s', $path))) {
            $asset->setPackageName('theme')->setPath($path);

            return;
        }

        $message = sprintf(
            "Couldn't add file asset '%s': File does not exist in either %s or %s directories.",
            $path,
            $this->getWebDirectory()->getFullPath(),
            $app['resources']->getUrl('theme')
        );
        $app['logger.system']->error($message, ['event' => 'extensions']);
    }

    /** @return Container */
    abstract protected function getContainer();

    /** @return string */
    abstract protected function getName();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();

    /** @return DirectoryInterface */
    abstract protected function getWebDirectory();
}
