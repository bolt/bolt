<?php

namespace Bolt\Extension;

use Bolt\Asset\AssetInterface;
use Bolt\Asset\File\FileAssetInterface;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Snippet\SnippetAssetInterface;
use Bolt\Asset\Widget\WidgetAssetInterface;
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
        $fileAsset->setFileName($this->getAssetPath($fileAsset->getFileName()));
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
        $fileAsset->setFileName($this->getAssetPath($fileAsset->getFileName()));
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
     * Get the relative path to the asset file.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $fileName
     *
     * @return string|null
     */
    private function getAssetPath($fileName)
    {
        $app = $this->getContainer();
        if (file_exists($this->getBasePath() . '/' . $fileName)) {
            return $this->getBaseUrl() . $fileName;
        } elseif (file_exists($app['resources']->getPath('themepath/' . $fileName))) {
            return $app['resources']->getUrl('theme') . $fileName;
        }

        $message = sprintf(
            "Couldn't add file asset '%s': File does not exist in either %s or %s directories.",
            $fileName,
            $this->getBaseUrl(),
            $app['resources']->getUrl('theme')
        );
        $app['logger.system']->error($message, ['event' => 'extensions']);

        return $fileName;
    }

    /**
     * Set up an asset.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param FileAssetInterface $asset
     * @param string             $fileName
     * @param array              $options
     *
     * @return FileAssetInterface
     */
    private function setupAsset(FileAssetInterface $asset, $fileName, array $options)
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
            ->setFileName($fileName)
            ->setLate($options['late'])
            ->setPriority($options['priority'])
            ->setAttributes($options['attrib'])
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
                'attrib'   => []
            ];
        }

        $options = $args[1];
        if (isset($options['attrib'])) {
            $options['attrib'] = explode(' ', $options['attrib']);
        }

        return $options;
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
