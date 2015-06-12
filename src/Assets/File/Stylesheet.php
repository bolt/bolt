<?php
namespace Bolt\Assets\File;

/**
 * Cascading stylesheet file object class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Stylesheet extends AssetBase
{
    /**
     * Return a string representation of the class.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('<link rel="stylesheet" href="%s" media="screen">', $this->fileName);
    }
}
