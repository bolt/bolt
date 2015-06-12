<?php
namespace Bolt\Assets\File;

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
        return sprintf('<script src="%s" %s></script>', $this->fileName, $this->attributes);
    }
}
