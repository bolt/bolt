<?php
namespace Bolt\Asset\File;

/**
 * JavaScript file object class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JavaScript extends FileAssetBase
{
    /** @var string */
    protected $type = 'javascript';

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('<script src="%s" %s></script>', $this->url, $this->getAttributes());
    }
}
