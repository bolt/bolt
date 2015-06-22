<?php
namespace Bolt\Asset\File;

/**
 * Cascading stylesheet file object class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Stylesheet extends FileAssetBase
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $hash = $this->cacheHash ? '?v=' . $this->cacheHash : $this->cacheHash;

        return sprintf('<link rel="stylesheet" href="%s%s" media="screen">', $this->fileName, $hash);
    }
}
