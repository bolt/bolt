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
     * Get the asset's file name.
     *
     * @return string
     */
    public function getFileName();

    /**
     * Set the file name for the asset.
     *
     * @param string $fileName
     *
     * @return FileAssetInterface
     */
    public function setFileName($fileName);

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

    /**
     * Get the cache hash string.
     *
     * @return string
     */
    public function getCacheHash();

    /**
     * Set the cache hash string.
     *
     * @param string $cacheHash
     *
     * @return AssetInterface
     */
    public function setCacheHash($cacheHash);
}
