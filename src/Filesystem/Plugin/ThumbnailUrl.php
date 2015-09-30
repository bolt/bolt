<?php

namespace Bolt\Filesystem\Plugin;

/**
 * @deprecated Since 2.3, will be removed in 3.0.
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
