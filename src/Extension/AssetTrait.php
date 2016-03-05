<?php

namespace Bolt\Extension;

use Bolt\Asset\AssetInterface;
use Bolt\Asset\File\FileAssetInterface;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Snippet\SnippetAssetInterface;
use Bolt\Asset\Widget\Widget;
use Bolt\Asset\Widget\WidgetAssetInterface;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Response\BoltResponse;
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
     * Add a particular CSS file to the output. This will be inserted before the
     * other css files.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use registerAssets() instead.
     *
     * @param FileAssetInterface|string $fileAsset Asset object, or file name
     */
    protected function addCss($fileAsset)
    {
        if (!$fileAsset instanceof FileAssetInterface) {
            $fileAsset = $this->setupAsset(new Stylesheet(), $fileAsset, func_get_args());
        }
        $this->normalizeAsset($fileAsset);
        $this->assets[] = $fileAsset;
    }

    /**
     * Add a particular javascript file to the output. This will be inserted after
     * the other javascript files.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use registerAssets() instead.
     *
     * @param FileAssetInterface|string $fileAsset File name
     */
    protected function addJavascript($fileAsset)
    {
        if (!$fileAsset instanceof FileAssetInterface) {
            $fileAsset = $this->setupAsset(new JavaScript(), $fileAsset, func_get_args());
        }
        $this->normalizeAsset($fileAsset);
        $this->assets[] = $fileAsset;
    }

    /**
     * Insert a snippet into the generated HTML.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use registerAssets() instead.
     *
     * @param string $location
     * @param string $callback
     * @param array  $callbackArguments
     */
    protected function addSnippet($location, $callback, $callbackArguments = [])
    {
        if ($callback instanceof BoltResponse) {
            $callback = (string) $callback;
        }

        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $snippet = (new Snippet())
            ->setLocation($location)
            ->setCallback($callback)
            ->setExtension($this->getName())
            ->setCallbackArguments((array) $callbackArguments)
        ;

        $this->assets[] = $snippet;
    }

    /**
     * Add a Widget to the render queue.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use registerAssets() instead.
     *
     * @param Widget $widget
     */
    protected function addWidget($widget)
    {
        if ($widget instanceof Widget) {
            $this->assets[] = $widget;
        }
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

    /**
     * Set up an asset.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param FileAssetInterface $asset
     * @param string             $path
     * @param array              $options
     *
     * @return FileAssetInterface
     */
    private function setupAsset(FileAssetInterface $asset, $path, array $options)
    {
        $options = array_merge(
            [
                'late'     => false,
                'priority' => 0,
                'attrib'   => [],
            ],
            $this->getCompatibleArgs($options)
        );

        $asset
            ->setPath($path)
            ->setLate($options['late'])
            ->setAttributes($options['attrib'])
            ->setPriority($options['priority'])
        ;

        return $asset;
    }

    /**
     * Get options that are compatible with Bolt 2.1 & 2.2 function signatures.
     * < 2.2 ($filename, $late = false, $priority = 0)
     * 2.2.x ($filename, $options = [])
     *
     * Where options were:
     *   'late'     - True to add to the end of the HTML <body>
     *   'priority' - Loading priority
     *   'attrib'   - A string containing either/both 'defer', and 'async'
     *
     * Passed in $args array can be:
     * - args[0] always the file name
     * - args[1] either $late     or $options[]
     * - args[2] either $priority or not set
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param array $args
     *
     * @return array
     */
    private function getCompatibleArgs(array $args)
    {
        if (empty($args[1]) || !is_array($args[1])) {
            return [
                'late'     => isset($args[1]) ? $args[1] : false,
                'priority' => isset($args[2]) ? $args[2] : 0,
                'attrib'   => [],
            ];
        }

        $options = $args[1];
        if (isset($options['attrib'])) {
            $options['attrib'] = explode(' ', $options['attrib']);
        }

        return $options;
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
