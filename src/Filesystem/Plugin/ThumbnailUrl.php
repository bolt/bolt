<?php

namespace Bolt\Filesystem\Plugin;

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
