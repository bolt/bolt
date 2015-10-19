<?php

namespace Bolt\Extensions;

use Bolt\Asset\File\FileAssetInterface;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;

/**
 * Extension assets BC trait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ExtensionAssetTrait
{
    abstract public function getBaseUrl();
    /** @return string */
    abstract public function getBasePath();

    /**
     * Add a particular CSS file to the output. This will be inserted before the
     * other css files.
     *
     * @param FileAssetInterface|string $fileName File name to add to href=""
     * @param array                     $options  Options:
     *                                            'late'     - True to add to the end of the HTML <body>
     *                                            'priority' - Loading priority
     *                                            'attrib'   - A string containing either/or 'defer', and 'async'
     */
    public function addCss($fileName, $options = [])
    {
        if (!$fileName instanceof FileAssetInterface) {
            // Handle pre-2.2 function parameters, namely $late and $priority
            if (!is_array($options)) {
                $options = $this->getCompatibleArgs(func_get_args());
            }
            $fileName = $this->getAssetPath($fileName);
            $fileName = $this->setupAsset(new Stylesheet(), $fileName, $options);
        }

        $this->app['asset.queue.file']->add($fileName);
    }

    /**
     * Add a particular javascript file to the output. This will be inserted after
     * the other javascript files.
     *
     * @param FileAssetInterface|string $fileName File name to add to src=""
     * @param array                     $options  Options:
     *                                            'late'     - True to add to the end of the HTML <body>
     *                                            'priority' - Loading priority
     *                                            'attrib'   - A string containing either/or 'defer', and 'async'
     */
    public function addJavascript($fileName, $options = [])
    {
        if (!$fileName instanceof FileAssetInterface) {
            // Handle pre-2.2 function parameters, namely $late and $priority
            if (!is_array($options)) {
                $options = $this->getCompatibleArgs(func_get_args());
            }
            $fileName = $this->getAssetPath($fileName);
            $fileName = $this->setupAsset(new JavaScript(), $fileName, $options);
        }
        $this->app['asset.queue.file']->add($fileName);
    }

    /**
     * Get the relative path to the asset file.
     *
     * @param string $fileName
     *
     * @return string|null
     */
    private function getAssetPath($fileName)
    {
        if (file_exists($this->getBasePath() . '/' . $fileName)) {
            return $this->getBaseUrl() . $fileName;
        } elseif (file_exists($this->app['resources']->getPath('themepath/' . $fileName))) {
            return $this->app['resources']->getUrl('theme') . $fileName;
        } elseif ($this instanceof \Bolt\Extensions) {
            return $fileName;
        } else {
            $message = sprintf(
                "Couldn't add file asset '%s': File does not exist in either %s or %s directories.",
                $fileName,
                $this->getBaseUrl(),
                $this->app['resources']->getUrl('theme')
            );
            $this->app['logger.system']->error($message, ['event' => 'extensions']);
        }
    }

    /**
     * Set up an asset.
     *
     * @param FileAssetInterface $asset
     * @param string             $fileName
     * @param array              $options
     */
    private function setupAsset(FileAssetInterface $asset, $fileName, array $options)
    {
        $options = array_merge(
            [
                'late'     => false,
                'priority' => 0,
                'attrib'   => null,
            ],
            $options
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
     * Get options that are compatible with Bolt 2.2.x function signature.
     *
     * @param array $args
     *
     * @return array
     */
    private function getCompatibleArgs(array $args)
    {
        return [
            'late'     => isset($args[1]) ? isset($args[1]) : false,
            'priority' => isset($args[2]) ? isset($args[2]) : 0,
            'attrib'   => false
        ];
    }
}
