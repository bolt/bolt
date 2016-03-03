<?php
namespace Bolt\Asset\File;

/**
 * Cascading stylesheet file object class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Stylesheet extends FileAssetBase
{
    /** @var string */
    protected $type = 'stylesheet';

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('<link rel="stylesheet" href="%s" media="screen">', $this->url);
    }
}
