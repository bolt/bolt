<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\TwigExtension;

class ThumbnailUrl extends AdapterPlugin
{
    /** @var TwigExtension */
    protected $twigHelper;

    public function getMethod()
    {
        return 'thumb';
    }

    public function getLocalThumb($path, $width, $height, $type)
    {
        $this->loadTwigExtension();

        return $this->twigHelper->thumbnail($path, $width, $height, $type);
    }

    protected function loadTwigExtension()
    {
        if (!$this->twigHelper) {
            $this->twigHelper = new TwigExtension($this->app);
        }
    }
}
