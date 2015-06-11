<?php
namespace Bolt\Assets\File;

/**
 * JavaScript file object class.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 */
class JavaScript extends AssetBase
{
    /**
     * Return a string representation of the class.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('<script src="%s"%s></script>', $this->fileName, $this->attributes);
    }
}
