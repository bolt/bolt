<?php

namespace Bolt\Asset\File;

use Bolt\Asset\AssetInterface;

/**
 * File asset interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface FileAssetInterface extends AssetInterface
{
    /**
     * Get the asset's type.
     *
     * @return string
     */
    public function getType();

    /**
     * Get the package name.
     *
     * @return string
     */
    public function getPackageName();

    /**
     * Set the package name.
     *
     * @param string $package
     *
     * @return FileAssetInterface
     */
    public function setPackageName($package);

    /**
     * Get the asset's path.
     *
     * @return string
     */
    public function getPath();

    /**
     * Set the asset's path.
     *
     * @param string $path
     *
     * @return FileAssetInterface
     */
    public function setPath($path);

    /**
     * Get the asset's url.
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set the asset's url.
     *
     * @param string $url
     *
     * @return FileAssetInterface
     */
    public function setUrl($url);

    /**
     * Check if the asset is set to load late.
     *
     * @return boolean
     */
    public function isLate();

    /**
     * Set the asset to load late.
     *
     * @param boolean $late
     *
     * @return FileAssetInterface
     */
    public function setLate($late);

    /**
     * Get the asset's attributes.
     *
     * @param boolean $raw
     *
     * @return string|array
     */
    public function getAttributes($raw = false);

    /**
     * Set the asset's attributes.
     *
     * @param array $attributes
     *
     * @return FileAssetInterface
     */
    public function setAttributes(array $attributes);

    /**
     * Add and attributes for the asset.
     *
     * @param string $attribute
     *
     * @return FileAssetInterface
     */
    public function addAttribute($attribute);
}
