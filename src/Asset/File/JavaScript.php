<?php
namespace Bolt\Asset\File;

/**
 * JavaScript file object class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JavaScript extends FileAssetBase
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $hash = $this->cacheHash ? '?v=' . $this->cacheHash : $this->cacheHash;

        return sprintf('<script src="%s%s" %s></script>', $this->fileName, $hash, $this->attributes);
    }
}
