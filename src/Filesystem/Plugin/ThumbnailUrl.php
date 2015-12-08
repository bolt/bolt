<?php

namespace Bolt\Filesystem\Plugin;

/**
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 */
class ThumbnailUrl extends AdapterPlugin
{
    public function getMethod()
    {
        return 'thumb';
    }

    public function getLocalThumb($path, $width, $height, $type)
    {
        return $this->app['twig.handlers']['image']->thumbnail($path, $width, $height, $type);
    }
}
